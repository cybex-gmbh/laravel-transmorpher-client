<?php

namespace Transmorpher;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Transmorpher\Enums\MediaType;
use Transmorpher\Enums\State;

class ImageTransmorpher extends Transmorpher
{
    /**
     * Create a new ImageTransmorpher and retrieves or creates the TransmorpherMedia for the specified model and differentiator.
     *
     * @param HasTransmorpherMediaInterface $model
     * @param string                        $differentiator
     */
    protected function __construct(protected HasTransmorpherMediaInterface $model, protected string $differentiator)
    {
        $this->transmorpherMedia = $model->TransmorpherMedia()->firstOrCreate(['differentiator' => $differentiator, 'type' => MediaType::IMAGE]);
    }

    /**
     * Upload an image to the Transmorpher.
     *
     * @return array The Transmorpher response.
     */
    public function upload($fileHandle): array
    {
        $request       = $this->configureApiRequest();
        $protocolEntry = $this->transmorpherMedia->TransmorpherProtocols()->create(['state' => State::PROCESSING, 'id_token' => $this->getIdToken()]);
        $response      = $request
            ->attach('image', $fileHandle)
            ->post($this->getApiUrl('image/upload'), ['identifier' => $this->getIdentifier()]);

        return $this->handleUploadResponse(json_decode($response->body(), true), $protocolEntry);
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
        return $this->configureApiRequest()->get($this->getApiUrl(sprintf('image/%s/version/%s', $this->getIdentifier(), $versionNumber)))->body();
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
}
