<?php

namespace Transmorpher;

use Illuminate\Http\Client\Response;
use Transmorpher\Enums\MediaType;
use Transmorpher\Enums\UploadState;
use Transmorpher\Models\TransmorpherUpload;

class VideoTransmorpher extends Transmorpher
{
    protected MediaType $type = MediaType::VIDEO;

    /**
     * Create a new VideoTransmorpher and retrieves or creates the TransmorpherMedia for the specified model and differentiator.
     *
     * @param HasTransmorpherMediaInterface $model
     * @param string $differentiator
     */
    protected function __construct(protected HasTransmorpherMediaInterface $model, protected string $differentiator)
    {
        $this->createTransmorpherMedia();
    }

    /**
     * @return string
     */
    public function getMp4Url(): string
    {
        return sprintf('%smp4/video.mp4', $this->getUrl());
    }

    /**
     * @return string
     */
    public function getHlsUrl(): string
    {
        return sprintf('%shls/video.m3u8', $this->getUrl());
    }

    /**
     * @return string
     */
    public function getDashUrl(): string
    {
        return sprintf('%sdash/video.mpd', $this->getUrl());
    }

    /**
     * @param array $clientResponse
     * @param TransmorpherUpload $upload
     *
     * @return void
     */
    public function updateModelsAfterSuccessfulUpload(array $clientResponse, TransmorpherUpload $upload)
    {
        $upload->update(['token' => $clientResponse['upload_token'], 'state' => UploadState::PROCESSING, 'message' => $clientResponse['response']]);
    }

    /**
     * @return string
     */
    public function getThumbnailUrl(): string
    {
        return $this->getMp4Url();
    }

    /**
     * Sends the request to reserve an upload slot to the Transmorpher media server API.
     * For videos, a callback URL has to be provided.
     *
     * @return Response
     */
    protected function sendReserveUploadSlotRequest(): Response
    {
        return $this->configureApiRequest()->post($this->getS2sApiUrl('video/reserveUploadSlot'), [
            'identifier' => $this->getIdentifier(),
            'callback_url' => sprintf('%s/%s', config('transmorpher.api.callback_base_url'), config('transmorpher.api.callback_route')),
        ]);
    }
}
