<?php

namespace Transmorpher;

use Illuminate\Support\Facades\Http;
use Transmorpher\Enums\TransmorpherApi;
use Transmorpher\Models\TransmorpherUpload;

abstract class StaticMedia extends Media
{
    /**
     * Retrieve the specified version as original.
     *
     * @param int $versionNumber The version which should be retrieved.
     *
     * @return array Binary string of the media and its content type.
     */
    public function getOriginal(int $versionNumber): array
    {
        $response = $this->configureApiRequest()->get(TransmorpherApi::S2S->getUrl(sprintf('%s/%s/version/%s/original', $this->type->value, $this->getIdentifier(), $versionNumber)));

        return ['binary' => $response->body(), 'mimetype' => $response->header('Content-Type')];
    }

    /**
     * Retrieve the specified version as derivative.
     *
     * @param int $versionNumber
     * @param string $transformations
     *
     * @return array
     */
    public function getDerivativeForVersion(int $versionNumber, string $transformations): array
    {
        $response = $this->configureApiRequest()->get(TransmorpherApi::S2S->getUrl(sprintf('%s/%s/version/%s/derivative/%s', $this->type->value, $this->getIdentifier(), $versionNumber, $transformations)));

        return ['binary' => $response->body(), 'mimetype' => $response->header('Content-Type')];
    }

    /**
     * Retrieve a derivative with optional transformations.
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
     * @param array $responseForClient
     * @param TransmorpherUpload $upload
     *
     * @return void
     */
    public function updateAfterSuccessfulUpload(array $responseForClient, TransmorpherUpload $upload): void
    {
        $this->transmorpherMedia->update(['is_ready' => 1, 'public_path' => $responseForClient['public_path'], 'hash' => $responseForClient['hash']]);
        $upload->update(['state' => $responseForClient['state'], 'message' => $responseForClient['message']]);
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
        return $this->getUrl(['height' => $this->getThumbnailHeight()]);
    }

    /**
     * @return array
     */
    public function getMediaUrls(): array
    {
        return [
            'fullsizeUrl' => $this->getUrl(),
            'thumbnailUrl' => $this->getThumbnailUrl(),
        ];

    }
}
