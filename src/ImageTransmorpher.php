<?php

namespace Transmorpher;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Transmorpher\Enums\MediaType;
use Transmorpher\Enums\State;
use Transmorpher\Models\TransmorpherUpload;

class ImageTransmorpher extends Transmorpher
{
    /**
     * Create a new ImageTransmorpher and retrieves or creates the TransmorpherMedia for the specified model and differentiator.
     *
     * @param HasTransmorpherMediaInterface $model
     * @param string $differentiator
     * @param MediaType $type
     *
     * @throws Exceptions\InvalidIdentifierException
     */
    protected function __construct(protected HasTransmorpherMediaInterface $model, protected string $differentiator, protected MediaType $type = MediaType::IMAGE)
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
        $response = $this->configureApiRequest()->get($this->getS2sApiUrl(sprintf('image/%s/version/%s', $this->getIdentifier(), $versionNumber)));

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
     * @return Response
     */
    protected function sendReserveUploadSlotRequest(): Response
    {
        return $this->configureApiRequest()->post($this->getS2sApiUrl('image/reserveUploadSlot'), ['identifier' => $this->getIdentifier()]);
    }

    /**
     * @param array $clientResponse
     * @param TransmorpherUpload $upload
     *
     * @return void
     */
    protected function updateModelsAfterSuccessfulUpload(array $clientResponse, TransmorpherUpload $upload)
    {
        $this->transmorpherMedia->update(['is_ready' => 1, 'public_path' => $clientResponse['public_path']]);
        $upload->update(['state' => State::SUCCESS, 'message' => $clientResponse['response']]);
    }
}
