<?php

namespace Transmorpher;

use Illuminate\Http\Client\Response;
use Transmorpher\Enums\MediaType;
use Transmorpher\Enums\TransmorpherApi;
use Transmorpher\Models\TransmorpherUpload;

class VideoTransmorpher extends Transmorpher
{
    protected MediaType $type = MediaType::VIDEO;

    /**
     * Create a new VideoTransmorpher and retrieves or creates the TransmorpherMedia for the specified model and topic.
     *
     * @param HasTransmorpherMediaInterface $model
     * @param string $topic
     */
    protected function __construct(protected HasTransmorpherMediaInterface $model, protected string $topic)
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
    public function updateAfterSuccessfulUpload(array $clientResponse, TransmorpherUpload $upload): void
    {
        $upload->update(['token' => $clientResponse['upload_token'], 'state' => $clientResponse['state'], 'message' => $clientResponse['message']]);
    }

    /**
     * @return string
     */
    public function getThumbnailUrl(): string
    {
        return $this->getMp4Url();
    }

    /**
     * Returns the accepted file mimetypes for this Transmorpher for use in e.g. Dropzone validation.
     *
     * @return string
     */
    public function getAcceptedFileTypes(): string
    {
        // Somehow video/* doesn't contain the .mkv mimetype.
        return 'video/*,video/x-matroska';
    }

    /**
     * Sends the request to reserve an upload slot to the Transmorpher media server API.
     * For videos, a callback URL has to be provided.
     *
     * @return Response
     */
    protected function sendReserveUploadSlotRequest(): Response
    {
        return $this->configureApiRequest()->post(TransmorpherApi::S2S->getUrl('video/reserveUploadSlot'), [
            'identifier' => $this->getIdentifier(),
            'callback_url' => sprintf('%s/%s', config('transmorpher.api.callback_base_url'), config('transmorpher.api.callback_route')),
        ]);
    }
}
