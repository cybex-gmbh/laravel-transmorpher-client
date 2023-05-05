<?php

namespace Transmorpher;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Transmorpher\Enums\MediaType;
use Transmorpher\Enums\State;
use Transmorpher\Exceptions\InvalidIdentifierException;

class ImageTransmorpher extends Transmorpher
{
    /**
     * Create a new ImageTransmorpher and retrieves or creates the TransmorpherMedia for the specified model and differentiator.
     *
     * @param HasTransmorpherMediaInterface $model
     * @param string $differentiator
     * @throws InvalidIdentifierException
     */
    protected function __construct(protected HasTransmorpherMediaInterface $model, protected string $differentiator)
    {
        $this->transmorpherMedia = $model->TransmorpherMedia()->firstOrCreate(['differentiator' => $differentiator, 'type' => MediaType::IMAGE]);

        $this->validateIdentifier($model, $differentiator);
    }

    /**
     * Upload an image to the Transmorpher.
     *
     * @param resource $fileHandle
     *
     * @return array The Transmorpher response.
     */
    public function upload($fileHandle): array
    {
        if (!is_resource($fileHandle)) {
            throw new InvalidArgumentException(sprintf('Argument must be a valid resource type, %s given.', gettype($fileHandle)));
        }

        $tokenResponse = $this->prepareUpload();
        $uploadEntry = $this->transmorpherMedia->TransmorpherUploads()->whereUploadToken($tokenResponse['upload_token'])->first();

        if (!$tokenResponse['success']) {
            return $this->handleUploadResponse($tokenResponse, $uploadEntry);
        }

        $request = $this->configureApiRequest();
        $response = $request
            ->attach('image', $fileHandle)
            ->post($this->getS2sApiUrl(sprintf('image/upload/%s', $tokenResponse['upload_token'])));

        return $this->handleUploadResponse(json_decode($response->body(), true), $uploadEntry);
    }

    /**
     * Retrieve the specified version as original image.
     *
     * @param int $versionNumber The version which should be retrieved.
     *
     * @return string Binary string of the image.
     */
    public function getOriginal(int $versionNumber): string
    {
        return $this->configureApiRequest()->get($this->getS2sApiUrl(sprintf('image/%s/version/%s', $this->getIdentifier(), $versionNumber)))->body();
    }

    /**
     * Retrieve a derivative image with optional transformations.
     *
     * @param array $transformations An array of transformations.
     *
     * @return string Binary string of the derivative.
     */
    public function getDerivative(array $transformations = []): string
    {
        return Http::get($this->getUrl($transformations))->body();
    }

    /**
     * Prepare an upload to the Transmorpher media server by requesting an upload token.
     *
     * @return array
     */
    public function prepareUpload(): array
    {
        $request = $this->configureApiRequest();
        $uploadEntry = $this->transmorpherMedia->TransmorpherUploads()->create(['state' => State::PROCESSING]);
        $response = $request->post($this->getS2sApiUrl('image/reserveUploadSlot'), ['identifier' => $this->getIdentifier()]);
        $body = json_decode($response, true);

        $success = $body['success'] ?? false;

        if ($success) {
            $this->transmorpherMedia->update(['last_upload_token' => $body['upload_token']]);
            $uploadEntry->update(['upload_token' => $body['upload_token']]);

            return [
                'success' => $success,
                'upload_token' => $body['upload_token'],
            ];
        }

        return [
            'success' => $success,
            'response' => $body['message'],
            'upload_token' => $uploadEntry->upload_token
        ];
    }

    /**
     * Get the web api url for uploads.
     *
     * @param string|null $uploadToken
     * @return string
     */
    public function getWebUploadUrl(string $uploadToken = null): string
    {
        return $this->getWebApiUrl('image/upload/' . $uploadToken);
    }

    /**
     * Get the route for receiving an upload token.
     *
     * @return string
     */
    public function getUploadTokenRoute(): string
    {
        return route('transmorpherImageToken');
    }

    /**
     * Get the max file size for uploads with dropzone.
     *
     * @return int
     */
    public function getMaxFileSize(): int
    {
        return config('transmorpher.dropzone_upload.image_max_file_size');
    }
}
