<?php

namespace Transmorpher;

use Illuminate\Support\Facades\Http;
use Transmorpher\Enums\MediaType;
use Transmorpher\Enums\TransmorpherApi;
use Transmorpher\Models\TransmorpherUpload;

class Image extends Media
{
    protected MediaType $type = MediaType::IMAGE;

    /**
     * Create a new Image and retrieve or create the TransmorpherMedia for the specified model and media name.
     *
     * @param HasTransmorpherMediaInterface $model
     * @param string $mediaName
     */
    protected function __construct(protected HasTransmorpherMediaInterface $model, protected string $mediaName)
    {
        $this->createTransmorpherMedia();
    }

    /**
     * Retrieve the specified version as original image.
     *
     * @param int $versionNumber The version which should be retrieved.
     *
     * @return array Binary string of the image and its content type.
     */
    public function getOriginal(int $versionNumber): array
    {
        $response = $this->configureApiRequest()->get(TransmorpherApi::S2S->getUrl(sprintf('image/%s/version/%s/original', $this->getIdentifier(), $versionNumber)));

        return ['binary' => $response->body(), 'mimetype' => $response->header('Content-Type')];
    }

    /**
     * @param int $versionNumber
     * @param string $transformations
     *
     * @return array
     */
    public function getDerivativeForVersion(int $versionNumber, string $transformations): array
    {
        $response = $this->configureApiRequest()->get(TransmorpherApi::S2S->getUrl(sprintf('image/%s/version/%s/derivative/%s', $this->getIdentifier(), $versionNumber, $transformations)));

        return ['binary' => $response->body(), 'mimetype' => $response->header('Content-Type')];
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
        return Http::get($this->getBaseUrl($transformations))->body();
    }

    /**
     * @param array $clientResponse
     * @param TransmorpherUpload $upload
     *
     * @return void
     */
    public function updateAfterSuccessfulUpload(array $clientResponse, TransmorpherUpload $upload): void
    {
        $this->transmorpherMedia->update(['is_ready' => 1, 'public_path' => $clientResponse['public_path'], 'hash' => $clientResponse['hash']]);
        $upload->update(['state' => $clientResponse['state'], 'message' => $clientResponse['message']]);
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
        if ($this->transmorpherMedia->isAvailable) {
            return sprintf(
                '%s/%s?v=%s',
                $this->getBaseUrl(),
                $this->getTransformations($transformations),
                $this->getCacheBuster()
            );
        }

        return $this->getPlaceholderUrl();
    }

    /**
     * @return string
     */
    public function getThumbnailUrl(): string
    {
        return $this->getUrl(['height' => 300]);
    }
}
