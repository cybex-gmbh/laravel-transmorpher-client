<?php

namespace Transmorpher;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Transmorpher\Enums\MediaType;
use Transmorpher\Enums\UploadState;
use Transmorpher\Models\TransmorpherUpload;

class ImageTransmorpher extends Transmorpher
{
    protected MediaType $type = MediaType::IMAGE;

    /**
     * Create a new ImageTransmorpher and retrieves or creates the TransmorpherMedia for the specified model and differentiator.
     *
     * @param HasTransmorpherMediaInterface $model
     * @param string $differentiator
     */
    protected function __construct(protected HasTransmorpherMediaInterface $model, protected string $differentiator)
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
        $response = $this->configureApiRequest()->get($this->getS2sApiUrl(sprintf('image/%s/version/%s/original', $this->getIdentifier(), $versionNumber)));

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
        $response = $this->configureApiRequest()->get($this->getS2sApiUrl(sprintf('image/%s/version/%s/derivative/%s', $this->getIdentifier(), $versionNumber, $transformations)));

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
        return Http::get($this->getUrl($transformations))->body();
    }

    /**
     * @param array $clientResponse
     * @param TransmorpherUpload $upload
     *
     * @return void
     */
    public function updateAfterSuccessfulUpload(array $clientResponse, TransmorpherUpload $upload)
    {
        $this->transmorpherMedia->update(['is_ready' => 1, 'public_path' => $clientResponse['public_path']]);
        $upload->update(['state' => UploadState::SUCCESS, 'message' => $clientResponse['response']]);
    }

    /**
     * @return string
     */
    public function getThumbnailUrl(): string
    {
        return $this->getUrl(['height' => 150]);
    }

    /**
     * Sends the request to reserve an upload slot to the Transmorpher media server API.
     *
     * @return Response
     */
    protected function sendReserveUploadSlotRequest(): Response
    {
        return $this->configureApiRequest()->post($this->getS2sApiUrl('image/reserveUploadSlot'), ['identifier' => $this->getIdentifier()]);
    }
}
