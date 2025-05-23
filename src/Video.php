<?php

namespace Transmorpher;

use Transmorpher\Enums\MediaType;
use Transmorpher\Models\TransmorpherUpload;

class Video extends Media
{
    public MediaType $type = MediaType::VIDEO;

    /**
     * @param string $format
     * @param string $extension
     * @return string|null
     */
    protected function getVideoUrl(string $format, string $extension): ?string
    {
        return  $this->transmorpherMedia->isAvailable ? sprintf('%s/%s/video.%s?v=%s', $this->getBaseUrl(), $format, $extension, $this->getCacheBuster()) : null;
    }

    /**
     * @return string|null
     */
    public function getMp4Url(): ?string
    {
        return $this->getVideoUrl('mp4', 'mp4');
    }

    /**
     * @return string|null
     */
    public function getHlsUrl(): ?string
    {
        return $this->getVideoUrl('hls', 'm3u8');
    }

    /**
     * @return string|null
     */
    public function getDashUrl(): ?string
    {
        return $this->getVideoUrl('dash', 'mpd');
    }

    /**
     * @param array $responseForClient
     * @param TransmorpherUpload $upload
     *
     * @return void
     */
    public function updateAfterSuccessfulUpload(array $responseForClient, TransmorpherUpload $upload): void
    {
        $upload->update(['token' => $responseForClient['upload_token'], 'state' => $responseForClient['state'], 'message' => $responseForClient['message']]);
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->getMp4Url();
    }

    /**
     * @return string|null
     */
    public function getThumbnailUrl(): ?string
    {
        return $this->transmorpherMedia->isAvailable ? $this->getMp4Url() : $this->getPlaceholderUrl();
    }

    /**
     * @return array
     */
    public function getMediaUrls(): array
    {
        return [
            'mp4Url' => $this->getMp4Url(),
            'hlsUrl' => $this->getHlsUrl(),
            'dashUrl' => $this->getDashUrl(),
            'thumbnailUrl' => $this->getThumbnailUrl(),
        ];
    }
}
