<?php

namespace Transmorpher;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Transmorpher\Enums\MediaType;
use Transmorpher\Enums\State;
use Transmorpher\Enums\Transformation;
use Transmorpher\Exceptions\InvalidIdentifierException;
use Transmorpher\Models\TransmorpherMedia;
use Transmorpher\Models\TransmorpherProtocol;

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
     * Create a new Transmorpher and retrieves or creates the TransmorpherMedia for the specified model and differentiator.
     *
     * @param HasTransmorpherMediaInterface $model
     * @param string                        $differentiator
     */
    protected abstract function __construct(HasTransmorpherMediaInterface $model, string $differentiator);

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
     * Delete all originals and derivatives for this differentiator on the Transmorpher.
     *
     * @return array The Transmorpher response.
     */
    public function delete(): array
    {
        $request       = $this->configureApiRequest();
        $protocolEntry = $this->transmorpherMedia->TransmorpherProtocols()->create(['state' => State::PROCESSING, 'id_token' => $this->getIdToken()]);
        $response      = $request->delete($this->getS2sApiUrl(sprintf('media/%s', $this->getIdentifier())));
        $body          = json_decode($response->body(), true);

        if ($body['success']) {
            $this->transmorpherMedia->update(['is_ready' => 0, 'last_response' => State::DELETED]);
            $protocolEntry->update(['state' => State::DELETED]);
        } else {
            $this->transmorpherMedia->update(['last_response' => State::ERROR]);
            $protocolEntry->update(['state' => State::ERROR, 'message' => $body['response']]);
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
            return sprintf('%s/%s/%s',
                $this->getDeliveryUrl(),
                $this->transmorpherMedia->public_path,
                $this->getTransformations($transformations),
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
        $request       = $this->configureApiRequest();
        $protocolEntry = $this->transmorpherMedia->TransmorpherProtocols()->create(['state' => State::PROCESSING, 'id_token' => $this->getIdToken()]);
        $response      = $request->patch($this->getS2sApiUrl(sprintf('media/%s/version/%s/set', $this->getIdentifier(), $versionNumber)), [
            'id_token'     => $protocolEntry->id_token,
            'callback_url' => route('transmorpherCallback'),
        ]);

        return $this->handleUploadResponse(json_decode($response->body(), true), $protocolEntry);
    }

    public function getTransmorpherMedia(): TransmorpherMedia
    {
        return $this->transmorpherMedia;
    }

    /**
     * Updates database fields for TransmorpherMedia and TransmorpherProtocol for a response.
     *
     * @param array                $body          The body of the response.
     * @param TransmorpherProtocol $protocolEntry The TransmorpherProtocol entry for the corresponding api request.
     *
     * @return array The response body.
     */
    public function handleUploadResponse(array $body, TransmorpherProtocol $protocolEntry): array
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
                $this->transmorpherMedia->update(['is_ready' => 1, 'last_response' => State::SUCCESS, 'public_path' => $body['public_path']]);
                $protocolEntry->update(['state' => State::SUCCESS]);
            } else {
                $this->transmorpherMedia->update(['last_response' => State::PROCESSING]);
            }
        } else {
            $this->transmorpherMedia->update(['last_response' => State::ERROR]);
            $protocolEntry->update(['state' => State::ERROR, 'message' => $body['response']]);
        }

        return $body;
    }

    /**
     * Get the identifier for this TransmorpherMedia.
     *
     * @return string The identifier for this TransmorpherMedia.
     */
    public function getIdentifier(): string
    {
        return sprintf('%s-%s-%s', $this->transmorpherMedia->differentiator, $this->transmorpherMedia->transmorphable_type, $this->transmorpherMedia->transmorphable_id);
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
     * Get an Id-Token.
     *
     * @return string The generated Id-Token.
     */
    protected function getIdToken(): string
    {
        return uniqid();
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
    protected function validateIdentifier(HasTransmorpherMediaInterface $model, string $differentiator): void
    {
        if (Str::contains($this->getIdentifier(), ['/', '\\', '.', ':'])) {
            $this->transmorpherMedia->delete();

            throw new InvalidIdentifierException($model, $differentiator);
        }
    }
}
