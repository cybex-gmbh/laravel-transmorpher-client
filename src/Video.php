<?php

namespace Transmorpher;

use Transmorpher\Enums\MediaType;
use Transmorpher\Models\TransmorpherUpload;

class Video extends Media
{
    protected MediaType $type = MediaType::VIDEO;

    /**
     * Create a new Video and retrieves or creates the TransmorpherMedia for the specified model and media name.
     *
     * @param HasTransmorpherMediaInterface $model
     * @param string $mediaName
     */
    protected function __construct(protected HasTransmorpherMediaInterface $model, protected string $mediaName)
    {
        $this->createTransmorpherMedia();
    }

    /**
     * @return string
     */
    public function getMp4Url(): string
    {
        return $this->getUrl('mp4', 'mp4');
    }

    /**
     * @return string
     */
    public function getHlsUrl(): string
    {
        return $this->getUrl('hls', 'm3u8');
    }

    /**
     * @return string
     */
    public function getDashUrl(): string
    {
        return $this->getUrl('dash', 'mpd');
    }

    protected function getUrl(string $format, string $extension): string
    {
        return sprintf('%s/%s/video.%s?v=%s', $this->getBaseUrl(), $format, $extension, $this->getCacheBuster());
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
}
