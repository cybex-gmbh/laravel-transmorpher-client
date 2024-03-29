<?php

namespace Transmorpher;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Transmorpher\Enums\ClientErrorResponse;
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

    /**
     * Get either an existing instance or creates a new one.
     *
     * @param HasTransmorpherMediaInterface $model A model which has TransmorpherMedia.
     * @param string $mediaName Specifies which media of the model it is.
     *
     * @return static The Media instance.
     */
    public static function for(HasTransmorpherMediaInterface $model, string $mediaName): static
    {
        return static::$instances[$model::class][$model->getKey()][$mediaName] ??= new static(...func_get_args());
    }

    /**
     * Create a new Media and retrieves or creates the TransmorpherMedia for the specified model and media name.
     *
     * @param HasTransmorpherMediaInterface $model
     * @param string $mediaName
     */
    protected abstract function __construct(HasTransmorpherMediaInterface $model, string $mediaName);

    /**
     * @param array $clientResponse
     * @param TransmorpherUpload $upload
     *
     * @return void
     */
    public abstract function updateAfterSuccessfulUpload(array $clientResponse, TransmorpherUpload $upload): void;

    /**
     * @return string
     */
    public abstract function getThumbnailUrl(): string;

    /**
     * @return Response
     */
    protected abstract function sendReserveUploadSlotRequest(): Response;

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
     */
    public function upload($fileHandle, string $fileName = null): array
    {
        // There is no type hint for resource.
        if (!is_resource($fileHandle)) {
            throw new InvalidArgumentException(sprintf('Argument must be a valid resource type, %s given.', gettype($fileHandle)));
        }

        $tokenResponse = $this->reserveUploadSlot();

        if ($tokenResponse['state'] === UploadState::ERROR->value) {
            return $this->upload->handleStateUpdate($tokenResponse);
        }

        $chunkSize = $this->getChunkSize();
        $chunkNumber = 1;
        $totalChunks = ceil(fstat($fileHandle)['size'] / $chunkSize);

        try {
            while (!feof($fileHandle)) {
                $chunk = fread($fileHandle, $chunkSize);

                $response = $this->configureApiRequest()
                    ->attach('file', $chunk, $fileName ?: basename(stream_get_meta_data($fileHandle)['uri']))
                    ->post(
                        TransmorpherApi::S2S->getUrl(sprintf('upload/%s', $tokenResponse['upload_token'])), [
                            'identifier' => $this->getIdentifier(),
                            'chunkNumber' => $chunkNumber++,
                            'totalChunks' => $totalChunks
                        ]
                    );
            }

            $clientResponse = $this->getClientResponseFromResponse($response);
        } catch (Exception $exception) {
            $clientResponse = ClientErrorResponse::NO_CONNECTION->getResponse(['message' => $exception->getMessage()]);
        }

        return $this->upload->handleStateUpdate($clientResponse);
    }

    /**
     * Handles reservation of an upload slot, also includes database interactions and retrieval of suitable client response.
     * The request itself is in the Image or Video class, since the API differs.
     *
     * @return array
     */
    public function reserveUploadSlot(): array
    {
        $this->upload = $this->transmorpherMedia->TransmorpherUploads()->create([
            'state' => UploadState::INITIALIZING,
            'message' => 'Sending request.',
        ]);

        try {
            $response = $this->sendReserveUploadSlotRequest();
            $clientResponse = $this->getClientResponseFromResponse($response);
        } catch (Exception $exception) {
            $clientResponse = ClientErrorResponse::NO_CONNECTION->getResponse(['message' => $exception->getMessage()]);
        }

        $valuesToUpdate = ['state' => $clientResponse['state'], 'message' => $clientResponse['message']];

        if ($clientResponse['state'] !== UploadState::ERROR->value) {
            $valuesToUpdate['token'] = $clientResponse['upload_token'];
        }

        $this->upload->update($valuesToUpdate);

        return $clientResponse;
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
            $response = $this->configureApiRequest()->delete(TransmorpherApi::S2S->getUrl(sprintf('media/%s', $this->getIdentifier())));
            $clientResponse = $this->getClientResponseFromResponse($response);
        } catch (Exception $exception) {
            $clientResponse = ClientErrorResponse::NO_CONNECTION->getResponse(['message' => $exception->getMessage()]);
        }

        if ($clientResponse['state'] === UploadState::DELETED->value) {
            $this->transmorpherMedia->update(['is_ready' => 0]);
        } else {
            if ($clientResponse['httpCode'] === 404) {
                $clientResponse['clientMessage'] = trans('transmorpher::errors.media_already_deleted');
            }
        }

        $upload->update(['state' => $clientResponse['state'], 'message' => $clientResponse['message']]);

        return $clientResponse;
    }

    /**
     * Get the public URL for retrieving a derivative with optional transformations.
     *
     * @param array $transformations Transformations in an array notation (e.g. ['width' => 1920, 'height' => 1080]).
     *
     * @return string The public URL to a derivative.
     */
    public function getUrl(array $transformations = []): string
    {
        if ($this->transmorpherMedia->is_ready && $this->transmorpherMedia->public_path) {
            return sprintf(
                '%s/%s/%s',
                $this->getDeliveryUrl(),
                $this->transmorpherMedia->public_path,
                $this->getTransformations($transformations)
            );
        }

        return $this->getPlaceholderUrl();
    }

    /**
     * @return string
     */
    public function getPlaceholderUrl(): string
    {
        return config('transmorpher.delivery.placeholder_url');
    }

    /**
     * Get all versions existing on the Transmorpher for this media.
     *
     * @return array The Transmorpher response.
     */
    public function getVersions(): array
    {
        return json_decode($this->configureApiRequest()->get(TransmorpherApi::S2S->getUrl(sprintf('media/%s/versions', $this->getIdentifier()))), true);
    }

    /**
     * Set a version as current version on the Transmorpher.
     *
     * @param int $versionNumber The version number which should be set as current.
     *
     * @return array The Transmorpher response.
     */
    public function setVersion(int $versionNumber): array
    {
        $upload = $this->transmorpherMedia->TransmorpherUploads()->create(['state' => UploadState::INITIALIZING, 'message' => 'Sending request to restore version.']);

        try {
            $response = $this->configureApiRequest()->patch(TransmorpherApi::S2S->getUrl(sprintf('media/%s/version/%s', $this->getIdentifier(), $versionNumber)), [
                'callback_url' => sprintf('%s/%s', config('transmorpher.api.callback_base_url'), config('transmorpher.api.callback_route')),
            ]);
            $clientResponse = $this->getClientResponseFromResponse($response);
        } catch (Exception $exception) {
            $clientResponse = ClientErrorResponse::NO_CONNECTION->getResponse(['message' => $exception->getMessage()]);
        }

        // HTTP code is only available in the response in case the request was not successful.
        if ($clientResponse['state'] === UploadState::ERROR->value && $clientResponse['httpCode'] === 404) {
            $clientResponse['clientMessage'] = trans('transmorpher::errors.version_no_longer_available');
        }

        return $upload->handleStateUpdate($clientResponse);
    }

    /**
     * @return TransmorpherMedia
     */
    public function getTransmorpherMedia(): TransmorpherMedia
    {
        return $this->transmorpherMedia;
    }

    /**
     * @param array $response The server response as an array.
     * @param int $httpCode
     * @return array The response body.
     */
    public function getClientResponse(array $response, int $httpCode): array
    {
        return isset($response['state']) && $response['state'] !== UploadState::ERROR->value ? $response : ClientErrorResponse::get($response, $httpCode);
    }

    /**
     * Wraps the "getClientResponse"-method to extract the body and http code from a response.
     *
     * @param Response $response
     * @return array
     */
    public function getClientResponseFromResponse(Response $response): array
    {
        return $this->getClientResponse(json_decode($response->body(), true), $response->status());
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
    public function getWebUploadUrl(string $uploadToken = null): string
    {
        return TransmorpherApi::WEB->getUrl('upload/' . $uploadToken);
    }

    /**
     * Get transformations as string.
     *
     * @param array $transformations An array of transformations.
     *
     * @return string The transformations converted to a string.
     */
    public function getTransformations(array $transformations): string
    {
        foreach ($transformations as $transformation => $value) {
            match ($transformation) {
                'width' => $transformationParts[] = Transformation::WIDTH->getUrlRepresentation($value),
                'height' => $transformationParts[] = Transformation::HEIGHT->getUrlRepresentation($value),
                'format' => $transformationParts[] = Transformation::FORMAT->getUrlRepresentation($value),
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
     * Get the configured client name.
     *
     * @return string The client name.
     */
    protected function getClientName(): string
    {
        return config('transmorpher.client_name');
    }

    /**
     * Get the Laravel Sanctum auth token.
     *
     * @return string The Laravel Sanctum auth token.
     */
    protected function getAuthToken(): string
    {
        return config('transmorpher.api.auth_token');
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
}
