<?php

namespace Transmorpher;

use InvalidArgumentException;
use Transmorpher\Enums\MediaType;
use Transmorpher\Enums\State;

class VideoTransmorpher extends Transmorpher
{
    /**
     * Create a new VideoTransmorpher and retrieves or creates the TransmorpherMedia for the specified model and differentiator.
     *
     * @param HasTransmorpherMediaInterface $model
     * @param string                        $differentiator
     */
    protected function __construct(protected HasTransmorpherMediaInterface $model, protected string $differentiator)
    {
        $this->transmorpherMedia = $model->TransmorpherMedia()->firstOrCreate(['differentiator' => $differentiator, 'type' => MediaType::VIDEO]);

        $this->validateIdentifier($model, $differentiator);
    }

    /**
     * Upload a video to the Transmorpher.
     *
     * @param resource $fileHandle
     *
     * @return array
     */
    public function upload($fileHandle): array
    {
        if (!is_resource($fileHandle)) {
            throw new InvalidArgumentException(sprintf('Argument must be a valid resource type, %s given.', gettype($fileHandle)));
        }

        $request       = $this->configureApiRequest();
        $protocolEntry = $this->transmorpherMedia->TransmorpherProtocols()->create(['state' => State::PROCESSING, 'id_token' => $this->getIdToken()]);

        $response = $request
            ->attach('video', $fileHandle)
            ->post($this->getApiUrl('video/upload'), [
                'identifier'   => $this->getIdentifier(),
                'id_token'     => $protocolEntry->id_token,
                'callback_url' => route('transmorpherCallback'),
            ]);

        return $this->handleUploadResponse(json_decode($response->body(), true), $protocolEntry);
    }

    public function getMp4Url(): string
    {
        return sprintf('%smp4/video.mp4', $this->getUrl());
    }

    public function getHlsUrl(): string
    {
        return sprintf('%shls/video.m3u8', $this->getUrl());
    }

    public function getDashUrl(): string
    {
        return sprintf('%sdash/video.mpd', $this->getUrl());
    }
}
