<?php

namespace Transmorpher;

use Illuminate\Support\Facades\Http;
use Transmorpher\Enums\MediaType;

class ImageTransmorpher extends Transmorpher
{
    /**
     * Create a new ImageTransmorpher and retrieves or creates the TransmorpherMedia for the specified model and differentiator.
     *
     * @param HasTransmorpherMediaInterface $model
     * @param string $differentiator
     */
    protected function __construct(protected HasTransmorpherMediaInterface $model, protected string $differentiator)
    {
        $this->createTransmorpherMedia(MediaType::IMAGE);
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
        return $this->uploadMedia($fileHandle, MediaType::IMAGE);
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
        return $this->prepareMediaUpload(MediaType::IMAGE);
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
