<?php

namespace Transmorpher;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Log;
use Transmorpher\Enums\ClientErrorResponse;
use Transmorpher\Enums\MediaType;
use Transmorpher\Enums\Transformation;
use Transmorpher\Enums\TransmorpherApi;
use Transmorpher\Enums\UploadState;
use Transmorpher\Exceptions\InvalidConfigurationValueException;
use Transmorpher\Exceptions\InvalidIdentifierException;
use Transmorpher\Exceptions\TransformationNotFoundException;
use Transmorpher\Models\TransmorpherMedia;
use Transmorpher\Models\TransmorpherUpload;

abstract class Media
{
    protected TransmorpherMedia $transmorpherMedia;
    protected static array $instances = [];
    protected TransmorpherUpload $upload;
    public MediaType $type;

    /**
     * Get either an existing instance or creates a new one.
     *
     * @param HasTransmorpherMediaInterface $model A model which has TransmorpherMedia.
     * @param string|null $mediaName Specifies which media of the model it is. Uses the name of the calling function as default.
     *
     * @return static The Media instance.
     */
    public static function for(HasTransmorpherMediaInterface $model, ?string $mediaName = null): static
    {
        $mediaName ??= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        return static::$instances[$model::class][$model->getKey()][$mediaName] ??= new static($model, $mediaName);
    }

    /**
     * Create a new Media and retrieve or create the TransmorpherMedia for the specified model and media name.
     *
     * @param HasTransmorpherMediaInterface $model
     * @param string $mediaName
     */
    protected function __construct(protected HasTransmorpherMediaInterface $model, protected string $mediaName)
    {
        $this->createTransmorpherMedia();
    }

    /**
     * @param array $responseForClient
     * @param TransmorpherUpload $upload
     *
     * @return void
     */
    public abstract function updateAfterSuccessfulUpload(array $responseForClient, TransmorpherUpload $upload): void;

    /**
     * @return string|null
     */
    public abstract function getUrl(): ?string;

    /**
     * @return string|null
     */
    public abstract function getThumbnailUrl(): ?string;

    /**
     * @return array
     */
    public abstract function getMediaUrls(): array;

    /**
     * @return void
     */
    protected function createTransmorpherMedia(): void
    {
        $this->validateIdentifier();

        $this->transmorpherMedia = $this->model->TransmorpherMedia()->firstOrCreate(
            ['media_name' => $this->mediaName, 'type' => $this->type],
            ['is_ready' => 0]
        );
    }

    /**
     * Upload media to the Transmorpher.
     *
     * @param resource $fileHandle
     *
     * @return array The Transmorpher response.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function upload($fileHandle, ?string $fileName = null): array
    {
        // There is no type hint for resource.
        if (!is_resource($fileHandle)) {
            throw new InvalidArgumentException(sprintf('Argument must be a valid resource type, %s given.', gettype($fileHandle)));
        }

        $uploadHandlerResponse = $this->getUploadHandler();

        if ($uploadHandlerResponse['state'] === UploadState::ERROR->value) {
            throw new Exception(sprintf('Failed to retrieve upload handler from server: %s', $uploadHandlerResponse['message']));
        }

        $fileName ??= basename(stream_get_meta_data($fileHandle)['uri']);

        $tokenResponse = $this->reserveUploadSlot($fileName);

        if ($tokenResponse['state'] === UploadState::ERROR->value) {
            return $this->upload->handleStateUpdate($tokenResponse);
        }

        $transmorpherUpload = $this->transmorpherMedia->TransmorpherUploads()->whereToken($tokenResponse['upload_token'])->first();

        $chunkSize = $this->getChunkSize();
        $chunkNumber = 1;
        $totalChunks = ceil(fstat($fileHandle)['size'] / $chunkSize);

        try {
            while (!feof($fileHandle)) {
                $responseForClient = $this->getChunkUploadUrl($transmorpherUpload, $chunkNumber);

                if (!isset($responseForClient['url'])) {
                    return $this->upload->handleStateUpdate($responseForClient);
                }

                // The server will return the web URL, we need to replace it with the S2S URL.
                $url = str_replace(TransmorpherApi::WEB->getUrl(), TransmorpherApi::S2S->getUrl(), $responseForClient['url']);
                $chunk = fread($fileHandle, $chunkSize);
                $responseFromServer = match ($uploadHandlerResponse['uploadHandler']) {
                    's3-multi-part' => Http::withBody($chunk)->put($url),
                    default => $this->configureApiRequest()
                        ->attach('file', $chunk, $fileName)
                        ->put($url, [
                            'identifier' => $this->getIdentifier(),
                            'chunkNumber' => $chunkNumber,
                            'totalChunks' => $totalChunks,
                        ]),
                };

                if (!$responseFromServer->ok()) {
                    break;
                }

                $chunkNumber++;
            }

            if (!$responseFromServer->ok()) {
                $responseForClient = $this->extractResponseForClient($responseFromServer);
            } else {
                $responseForClient = $this->completeUpload($transmorpherUpload);
            }
        } catch (Exception $exception) {
            $responseForClient = ClientErrorResponse::NO_CONNECTION->getResponse(['message' => $exception->getMessage()]);
        }

        // Will abort the upload in case of error.
        return $this->upload->handleStateUpdate($responseForClient);
    }

    /**
     * Handles reservation of an upload slot, also includes database interactions and retrieval of suitable client response.
     *
     * @param string $filename
     *
     * @return array
     */
    public function reserveUploadSlot(string $filename): array
    {
        $this->upload = $this->transmorpherMedia->TransmorpherUploads()->create([
            'state' => UploadState::INITIALIZING,
            'message' => 'Sending request.',
        ]);
        $reserveUploadSlotUrl = TransmorpherApi::S2S->getUrl(sprintf('%s/upload/reserve', $this->type->value));

        try {
            $responseFromServer = $this->configureApiRequest()->post($reserveUploadSlotUrl, ['identifier' => $this->getIdentifier(), 'filename' => $filename]);
            $responseForClient = $this->extractResponseForClient($responseFromServer);
        } catch (Exception $exception) {
            $responseForClient = ClientErrorResponse::NO_CONNECTION->getResponse(['message' => $exception->getMessage()]);
        }

        $valuesToUpdate = ['state' => $responseForClient['state'], 'message' => $responseForClient['message']];

        if ($responseForClient['state'] !== UploadState::ERROR->value) {
            $valuesToUpdate['token'] = $responseForClient['upload_token'];
        }

        $this->upload->update($valuesToUpdate);

        return $responseForClient;
    }

    public function getChunkUploadUrl(TransmorpherUpload $upload, int $chunkNumber): array
    {
        $getChunkUploadUrlUrl = TransmorpherApi::S2S->getUrl(sprintf('upload/%s/chunkUrl/%s', $upload->token, $chunkNumber));

        try {
            $responseFromServer = $this->configureApiRequest()->get($getChunkUploadUrlUrl);
        } catch (Exception $exception) {
            return ClientErrorResponse::NO_CONNECTION->getResponse(['message' => $exception->getMessage()]);
        }

        return $responseFromServer->ok()
            ? json_decode($responseFromServer->body(), associative: true)
            : $this->extractResponseForClient($responseFromServer);
    }

    public function completeUpload(TransmorpherUpload $upload): array
    {
        $completeUploadUrl = TransmorpherApi::S2S->getUrl(sprintf('upload/%s/complete', $upload->token));

        try {
            $responseFromServer = $this->configureApiRequest()->post($completeUploadUrl);
            $responseForClient = $this->extractResponseForClient($responseFromServer);
        } catch (Exception $exception) {
            $responseForClient = ClientErrorResponse::NO_CONNECTION->getResponse(['message' => $exception->getMessage()]);
        }

        return $responseForClient;
    }

    public function abortUpload(TransmorpherUpload $upload): array
    {
        $abortUploadUrl = TransmorpherApi::S2S->getUrl(sprintf('upload/%s', $upload->token));

        try {
            $responseFromServer = $this->configureApiRequest()->delete($abortUploadUrl);
            $responseForClient = $this->extractResponseForClient($responseFromServer);
        } catch (Exception $exception) {
            $responseForClient = ClientErrorResponse::NO_CONNECTION->getResponse(['message' => $exception->getMessage()]);
        }

        if ($responseForClient['state'] === UploadState::ERROR->value) {
            Log::error(sprintf('Failed to abort file upload. Message: %s', $responseForClient['message']));
        }

        return $responseForClient;
    }

    /**
     * Delete all originals and derivatives for this media on the Transmorpher.
     *
     * @return array The Transmorpher response.
     */
    public function delete(): array
    {
        $upload = $this->transmorpherMedia->TransmorpherUploads()->create(['state' => UploadState::INITIALIZING, 'message' => 'Sending delete request.']);

        try {
            $responseFromServer = $this->configureApiRequest()->delete(TransmorpherApi::S2S->getUrl(sprintf('media/%s', $this->getIdentifier())));
            $responseForClient = $this->extractResponseForClient($responseFromServer);
        } catch (Exception $exception) {
            $responseForClient = ClientErrorResponse::NO_CONNECTION->getResponse(['message' => $exception->getMessage()]);
        }

        if ($responseForClient['state'] === UploadState::DELETED->value) {
            $this->transmorpherMedia->update(['is_ready' => 0]);
        } else {
            if ($responseForClient['httpCode'] === 404) {
                $responseForClient['clientMessage'] = trans('transmorpher::errors.media_already_deleted');
            }
        }

        $upload->update(['state' => $responseForClient['state'], 'message' => $responseForClient['message']]);

        return array_merge($responseForClient, [
            'placeholderUrl' => $this->getPlaceholderUrl()
        ]);
    }

    /**
     * Get the public base URL for retrieving a derivative.
     *
     * @return string The public base URL to a derivative.
     */
    protected function getBaseUrl(): string
    {
        return sprintf(
            '%s/%s',
            $this->getDeliveryUrl(),
            $this->transmorpherMedia->public_path,
        );
    }

    /**
     * @return string
     */
    public function getPlaceholderUrl(): string
    {
        return config('transmorpher.delivery.placeholder.url');
    }

    /**
     * Get all versions existing on the Transmorpher for this media.
     *
     * @return array The Transmorpher response.
     */
    public function getVersions(): array
    {
        return array_merge(
            json_decode(
                $this->configureApiRequest()->get(TransmorpherApi::S2S->getUrl(sprintf('media/%s/versions', $this->getIdentifier()))),
                true
            ),
            $this->getMediaUrls());
    }

    /**
     * Set a version as the current version on the Transmorpher.
     *
     * @param int $versionNumber The version number which should be set as current.
     *
     * @return array The Transmorpher response.
     */
    public function setVersion(int $versionNumber): array
    {
        $upload = $this->transmorpherMedia->TransmorpherUploads()->create(['state' => UploadState::INITIALIZING, 'message' => 'Sending request to restore version.']);

        try {
            $responseFromServer = $this->configureApiRequest()->patch(TransmorpherApi::S2S->getUrl(sprintf('media/%s/versions/%s', $this->getIdentifier(), $versionNumber)));
            $responseForClient = $this->extractResponseForClient($responseFromServer);
        } catch (Exception $exception) {
            $responseForClient = ClientErrorResponse::NO_CONNECTION->getResponse(['message' => $exception->getMessage()]);
        }

        // HTTP code is only available in the response in case the request was not successful.
        if ($responseForClient['state'] === UploadState::ERROR->value && $responseForClient['httpCode'] === 404) {
            $responseForClient['clientMessage'] = trans('transmorpher::errors.version_no_longer_available');
        }

        return $upload->handleStateUpdate($responseForClient);
    }

    /**
     * @return TransmorpherMedia
     */
    public function getTransmorpherMedia(): TransmorpherMedia
    {
        return $this->transmorpherMedia;
    }

    /**
     * @param array $responseFromServer The server response as an array.
     * @param int $httpCode
     * @return array The response body.
     */
    public function generateResponseForClient(array $responseFromServer, int $httpCode): array
    {
        return isset($responseFromServer['state']) && $responseFromServer['state'] !== UploadState::ERROR->value ? $responseFromServer : ClientErrorResponse::get($responseFromServer, $httpCode);
    }

    /**
     * Wraps the "generateResponseForClient"-method to extract the body and http code from a response.
     *
     * @param Response $responseFromServer
     * @return array
     */
    public function extractResponseForClient(Response $responseFromServer): array
    {
        return $this->generateResponseForClient(json_decode($responseFromServer->body(), true), $responseFromServer->status());
    }

    /**
     * Get the identifier for this TransmorpherMedia.
     * This identifier is used on the Transmorpher server to uniquely identify a media per user.
     * It should not contain any special characters such as slashes, since it will be used in URLs.
     *
     * @return string The identifier for this TransmorpherMedia.
     */
    public function getIdentifier(): string
    {
        return sprintf('%s-%s-%s', $this->model->getTransmorpherAlias(), $this->model->getKey(), $this->mediaName);
    }

    /**
     * Get the public URL to retrieve a derivative.
     *
     * @return string The public URL.
     */
    public function getDeliveryUrl(): string
    {
        return config('transmorpher.delivery.url');
    }

    /**
     * Get the web api URL for uploads.
     *
     * @param string|null $uploadToken
     * @return string
     */
    public function getWebUploadUrl(?string $uploadToken = null): string
    {
        return TransmorpherApi::WEB->getUrl('upload/' . $uploadToken);
    }

    /**
     * Get transformations as string.
     *
     * @param array $transformations An array of transformations.
     *
     * @return string The transformations converted to a string.
     * @throws TransformationNotFoundException
     */
    public function getTransformations(array $transformations): string
    {
        foreach ($transformations as $transformation => $value) {
            match ($transformation) {
                'width' => $transformationParts[] = Transformation::WIDTH->getUrlRepresentation($value),
                'height' => $transformationParts[] = Transformation::HEIGHT->getUrlRepresentation($value),
                'format' => $transformationParts[] = Transformation::FORMAT->getUrlRepresentation($value),
                'page' => $transformationParts[] = Transformation::PAGE->getUrlRepresentation($value),
                'ppi' => $transformationParts[] = Transformation::PPI->getUrlRepresentation($value),
                'quality' => $transformationParts[] = Transformation::QUALITY->getUrlRepresentation($value),
                default => throw new TransformationNotFoundException($transformation)
            };
        }

        return implode('+', $transformationParts ?? []);
    }

    /**
     * Get the configured chunk size for chunked uploads.
     *
     * @return int
     */
    public function getChunkSize(): int
    {
        return config('transmorpher.upload.chunk_size');
    }

    /**
     * Get the max file size for uploads with dropzone.
     *
     * @return int
     */
    public function getMaxFileSize(): int
    {
        return config(sprintf('transmorpher.upload.%s.validations.max_file_size', $this->type->value));
    }

    /**
     * Returns the accepted file mimetypes for this Media for use in e.g. Dropzone validation.
     *
     * @return string
     */
    public function getAcceptedFileTypes(): string
    {
        return config(sprintf('transmorpher.upload.%s.validations.mimetypes', $this->type->value));
    }

    /**
     * Returns the accepted min width for this Media for use in e.g. Dropzone validation.
     *
     * @return int|null
     */
    public function getMinWidth(): ?int
    {
        return config(sprintf('transmorpher.upload.%s.validations.dimensions.width.min', $this->type->value));
    }

    /**
     * Returns the accepted max width for this Media for use in e.g. Dropzone validation.
     *
     * @return int|null
     */
    public function getMaxWidth(): ?int
    {
        return config(sprintf('transmorpher.upload.%s.validations.dimensions.width.max', $this->type->value));
    }

    /**
     * Returns the accepted min height for this Media for use in e.g. Dropzone validation.
     *
     * @return int|null
     */
    public function getMinHeight(): ?int
    {
        return config(sprintf('transmorpher.upload.%s.validations.dimensions.height.min', $this->type->value));
    }

    /**
     * Returns the accepted max height for this Media for use in e.g. Dropzone validation.
     *
     * @return int|null
     */
    public function getMaxHeight(): ?int
    {
        return config(sprintf('transmorpher.upload.%s.validations.dimensions.height.max', $this->type->value));
    }

    /**
     * Returns the accepted ratio for this Media as string.
     *
     * @return string|null
     */
    public function getDisplayRatio(): ?string
    {
        return config(sprintf('transmorpher.upload.%s.validations.dimensions.ratio', $this->type->value));
    }

    /**
     * Returns the accepted ratio for this Media as float for use in e.g. Dropzone validation.
     *
     * @param string|null $displayRatio
     * @return float|null
     * @throws InvalidConfigurationValueException
     */
    public function getCalculatedRatio(?string $displayRatio): ?float
    {
        if (!$displayRatio) {
            return null;
        }

        if (!preg_match('/^(\d+):(\d+)$/', $displayRatio, $matches)) {
            throw new InvalidConfigurationValueException('ratio', $displayRatio);
        }

        return $matches[1] / $matches[2];
    }

    /**
     * Get the default transformations for the preview image displayed as thumbnail on the dropzone component.
     *
     * @return array
     */
    public function getThumbnailDefaultTransformations(): array
    {
        return [];
    }

    /**
     * Get the upload handler configured on the Transmorpher Server.
     *
     * @return array|string
     *
     * @throws ConnectionException
     */
    public function getUploadHandler(): array|string
    {
        try {
            $responseFromServer = $this->configureApiRequest()->get(TransmorpherApi::S2S->getUrl('meta/uploadHandler'));
        } catch (Exception $exception) {
            return ClientErrorResponse::NO_CONNECTION->getResponse(['message' => $exception->getMessage()]);
        }

        if (!$responseFromServer->ok()) {
            return $this->extractResponseForClient($responseFromServer);
        }

        return [
            'state' => UploadState::SUCCESS,
            'uploadHandler' => $responseFromServer->body()
        ];
    }

    /**
     * Configure an HTTP-Request with the Laravel Sanctum Token.
     *
     * @return PendingRequest The configured request.
     */
    protected function configureApiRequest(): PendingRequest
    {
        return Http::withToken($this->getAuthToken())
            ->withOptions(['stream' => true])
            ->acceptJson()
            ->withoutRedirecting();
    }

    /**
     * Get the Laravel Sanctum auth token.
     *
     * @return string The Laravel Sanctum auth token.
     */
    protected function getAuthToken(): string
    {
        return config('transmorpher.api.auth.token');
    }

    /**
     * Validate the identifier to make sure it doesn't contain forbidden characters.
     * @throws InvalidIdentifierException
     */
    protected function validateIdentifier(): void
    {
        // Identifier is used in file paths and URLs, therefore only alphanumeric characters, underscores and hyphens are allowed.
        if (!preg_match('/^\w(-?\w)*$/', $this->getIdentifier())) {
            throw new InvalidIdentifierException($this->model, $this->mediaName);
        }
    }

    /**
     * Get the cache buster.
     * The cache invalidator received from the Transmorpher server is stored in the cache for 14 days.
     *
     * @return string
     */
    protected function getCacheBuster(): string
    {
        $cacheBuster = Cache::remember('cache_invalidator', now()->addDays(14), function () {
            return $this->configureApiRequest()->get(TransmorpherApi::S2S->getUrl('meta/cacheInvalidator'))->body();
        });

        return sprintf(
            '%s_%s',
            $cacheBuster,
            $this->transmorpherMedia->hash ?? md5($this->transmorpherMedia->latestSuccessfulUpload->updated_at)
        );
    }

    /**
     * Get the configured thumbnail height.
     *
     * @return int
     */
    protected function getThumbnailHeight(): int
    {
        return config('transmorpher.delivery.thumbnail.transformations.height', 300);
    }
}
