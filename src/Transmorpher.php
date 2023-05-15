<?php

namespace Transmorpher;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Transmorpher\Enums\MediaType;
use Transmorpher\Enums\State;
use Transmorpher\Enums\Transformation;
use Transmorpher\Exceptions\InvalidIdentifierException;
use Transmorpher\Models\TransmorpherMedia;
use Transmorpher\Models\TransmorpherUpload;

abstract class Transmorpher
{
    protected TransmorpherMedia    $transmorpherMedia;
    protected static array         $instances = [];

    /**
     * Get either an existing instance or creates a new one.
     *
     * @param HasTransmorpherMediaInterface $model          A model which has TransmorpherMedia.
     * @param string                        $differentiator The Differentiator identifying the TransmorpherMedia.
     *
     * @return static The Transmorpher instance.
     */
    public static function getInstanceFor(HasTransmorpherMediaInterface $model, string $differentiator): static
    {
        return static::$instances[$model::class][$model->getKey()][$differentiator] ??= new static(...func_get_args());
    }

    /**
     * Prepare an upload to the Transmorpher media server by requesting an upload token.
     *
     * @return array
     */
    public function reserveUploadSlot(): array
    {
        $request = $this->configureApiRequest();
        $upload  = $this->transmorpherMedia->TransmorpherUploads()->create([
            'state'   => State::INITIALIZING,
            'message' => 'Sending request.',
        ]);

        try {
            if ($this->type === MediaType::IMAGE) {
                $response = $request->post($this->getS2sApiUrl('image/reserveUploadSlot'), ['identifier' => $this->getIdentifier()]);
            } else {
                $response = $request->post($this->getS2sApiUrl('video/reserveUploadSlot'), [
                    'identifier' => $this->getIdentifier(),
                    'callback_url' => sprintf('%s/%s', config('transmorpher.api.callback_base_url'), config('transmorpher.api.callback_route')),
                ]);
            }

            $body = json_decode($response->body(), true);
        } catch (Exception $exception) {
            $message = 'Could not connect to server.';
            $upload->update(['state' => State::ERROR, 'message' => $exception->getMessage()]);
        }

        $success = $body['success'] ?? false;

        if ($success) {
            $upload->update(['token' => $body['upload_token'], 'message' => $body['response']]);

            return [
                'success' => $success,
                'upload_token' => $body['upload_token'],
            ];
        }

        return [
            'success' => $success,
            'response' => $message ?? $body['message'],
            'upload_token' => $upload->token
        ];
    }

    /**
     * Delete all originals and derivatives for this differentiator on the Transmorpher.
     *
     * @return array The Transmorpher response.
     */
    public function delete(): array
    {
        $request = $this->configureApiRequest();
        $upload = $this->transmorpherMedia->TransmorpherUploads()->create(['state' => State::INITIALIZING, 'message' => 'Sending delete request.']);

        try {
            $response = $request->delete($this->getS2sApiUrl(sprintf('media/%s', $this->getIdentifier())));
            $body = json_decode($response->body(), true);
        } catch (Exception $exception) {
            $upload->update(['state' => State::ERROR, 'message' => $exception->getMessage()]);

            return [
                'success' => false,
                'response' => 'Could not connect to server.'
            ];
        }

        if ($body['success']) {
            $this->transmorpherMedia->update(['is_ready' => 0]);
            $upload->update(['state' => State::DELETED, 'message' => $body['response']]);
        } else {
            $upload->update(['state' => State::ERROR, 'message' => $body['response']]);
        }

        return $body;
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

        return config('transmorpher.delivery.placeholder_url');
    }

    /**
     * Get all versions existing on the Transmorpher for this differentiator.
     *
     * @return array The Transmorpher response.
     */
    public function getVersions(): array
    {
        return json_decode($this->configureApiRequest()->get($this->getS2sApiUrl(sprintf('media/%s/versions', $this->getIdentifier()))), true);
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
        $request = $this->configureApiRequest();
        $upload = $this->transmorpherMedia->TransmorpherUploads()->create(['state' => State::INITIALIZING, 'message' => 'Sending request to restore version.']);

        try {
            $response = $request->patch($this->getS2sApiUrl(sprintf('media/%s/version/%s/set', $this->getIdentifier(), $versionNumber)), [
                'callback_url' => sprintf('%s/%s', config('transmorpher.api.callback_base_url'), config('transmorpher.api.callback_route')),
            ]);

            $body = json_decode($response->body(), true);
        } catch (Exception $exception) {
            $body = [
                'success'  => false,
                'response' => 'Could not connect to server.'
            ];
        }

        return $this->handleUploadResponse($body, $upload);
    }

    public function getTransmorpherMedia(): TransmorpherMedia
    {
        return $this->transmorpherMedia;
    }

    /**
     * Updates database fields for TransmorpherMedia and TransmorpherUpload for a response.
     *
     * @param array              $body   The body of the response.
     * @param TransmorpherUpload $upload The TransmorpherUpload entry for the corresponding api request.
     *
     * @return array The response body.
     */
    public function handleUploadResponse(array $body, TransmorpherUpload $upload): array
    {
        // An error was returned from the server.
        if (array_key_exists('message', $body)) {
            $body = [
                'success'  => false,
                'response' => $body['exception'] ?? $body['message'],
            ];
        }

        if ($body['success']) {
            if ($this->transmorpherMedia->type === MediaType::IMAGE) {
                $this->transmorpherMedia->update(['is_ready' => 1, 'public_path' => $body['public_path']]);
                $upload->update(['state' => State::SUCCESS, 'message' => $body['response']]);
            } else {
                $upload->update(['token' => $body['upload_token'], 'state' => State::PROCESSING, 'message' => $body['response']]);
            }
        } else {
            $upload->update(['state' => State::ERROR, 'message' => $body['response']]);
        }

        return $body;
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
        return sprintf('%s-%s-%s', $this->differentiator, $this->model->getMorphClass(), $this->model->getKey());
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
        return $this->getWebApiUrl('upload/' . $uploadToken);
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
                'width'   => $transformationParts[] = Transformation::WIDTH->getUrlRepresentation($value),
                'height'  => $transformationParts[] = Transformation::HEIGHT->getUrlRepresentation($value),
                'format'  => $transformationParts[] = Transformation::FORMAT->getUrlRepresentation($value),
                'quality' => $transformationParts[] = Transformation::QUALITY->getUrlRepresentation($value),
                default   => null
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
        return config('transmorpher.dropzone_upload.chunk_size');
    }

    /**
     * Get the max file size for uploads with dropzone.
     *
     * @return int
     */
    public function getMaxFileSize(): int
    {
        return config(sprintf('transmorpher.dropzone_upload.%s.max_file_size', $this->type->value));
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
     * @return void
     * @throws InvalidIdentifierException
     */
    protected function createTransmorpherMedia() {
        $this->validateIdentifier();

        $this->transmorpherMedia = $this->model->TransmorpherMedia()->firstOrCreate(
            ['differentiator' => $this->differentiator, 'type' => $this->type],
            ['is_ready' => 0]
        );
    }

    /**
     * Create a new Transmorpher and retrieves or creates the TransmorpherMedia for the specified model and differentiator.
     *
     * @param HasTransmorpherMediaInterface $model
     * @param string                        $differentiator
     */
    protected abstract function __construct(HasTransmorpherMediaInterface $model, string $differentiator);

    /**
     * Upload media to the Transmorpher.
     *
     * @param resource $fileHandle
     *
     * @return array The Transmorpher response.
     */
    protected function upload($fileHandle): array
    {
        if (!is_resource($fileHandle)) {
            throw new InvalidArgumentException(sprintf('Argument must be a valid resource type, %s given.', gettype($fileHandle)));
        }

        $tokenResponse = $this->reserveUploadSlot();
        $upload = $this->transmorpherMedia->TransmorpherUploads()->whereToken($tokenResponse['upload_token'])->first();

        if (!$tokenResponse['success']) {
            return $this->handleUploadResponse($tokenResponse, $upload);
        }

        $request = $this->configureApiRequest();

        try {
            $response = $request
                ->attach('file', $fileHandle)
                ->post($this->getS2sApiUrl(sprintf('%s/upload/%s', $this->type->value, $tokenResponse['upload_token'])));

            $body = json_decode($response->body(), true);
        } catch (Exception $exception) {
            $body = [
                'success' => false,
                'response' => 'Could not connect to server.'
            ];
        }

        return $this->handleUploadResponse($body, $upload);
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
     * Get the s2s api URL to make calls to the Transmorpher.
     *
     * @param string|null $path An optional path which gets included in the URL.
     *
     * @return string The s2s api URL.
     */
    protected function getS2sApiUrl(string $path = null): string
    {
        return sprintf('%s/%s', config('transmorpher.api.s2s_url'), $path);
    }

    /**
     * Get the web api URL to make calls to the Transmorpher.
     *
     * @param string|null $path An optional path which gets included in the URL.
     *
     * @return string The web api URL.
     */
    protected function getWebApiUrl(string $path = null): string
    {
        return sprintf('%s/%s', config('transmorpher.api.web_url'), $path);
    }

    /**
     * Validate the identifier to make sure it doesn't contain forbidden characters.
     * @throws InvalidIdentifierException
     */
    protected function validateIdentifier(): void
    {
        // Only allow lower/uppercase characters, numbers, underscores and dashes.
        // The first character can't be a dash.
        if (!preg_match('/^[\w][\w\-]*$/', $this->getIdentifier())) {
            throw new InvalidIdentifierException($this->model, $this->differentiator);
        }
    }
}
