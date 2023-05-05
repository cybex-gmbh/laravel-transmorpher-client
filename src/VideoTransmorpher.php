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

        $tokenResponse = $this->prepareUpload();
        $uploadEntry = $this->transmorpherMedia->TransmorpherUploads()->whereUploadToken($tokenResponse['upload_token'])->first();

        if (!$tokenResponse['success']) {
            return $this->handleUploadResponse($tokenResponse, $uploadEntry);
        }

        $request = $this->configureApiRequest();
        $response = $request
            ->attach('video', $fileHandle)
            ->post($this->getS2sApiUrl(sprintf('video/upload/%s', $tokenResponse['upload_token'])));

        return $this->handleUploadResponse(json_decode($response->body(), true), $uploadEntry);
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

    /**
     * Prepare an upload to the Transmorpher media server by requesting an upload token.
     *
     * @return array
     */
    public function prepareUpload(): array
    {
        $request = $this->configureApiRequest();
        $uploadEntry = $this->transmorpherMedia->TransmorpherUploads()->create(['state' => State::PROCESSING]);
        $response = $request->post($this->getS2sApiUrl('video/reserveUploadSlot'), [
            'identifier' => $this->getIdentifier(),
            'callback_url' => sprintf('%s/%s', config('transmorpher.api.callback_base_url'), config('transmorpher.api.callback_route')),
        ]);
        $body = json_decode($response, true);

        $success = $body['success'] ?? false;

        if ($success) {
            $this->transmorpherMedia->update(['last_upload_token' => $body['upload_token']]);
            $uploadEntry->update(['upload_token' => $body['upload_token']]);

            return [
                'success' => $success,
                'upload_token' => $body['upload_token'],
            ];
        }

        return [
            'success' => $success,
            'response' => $body['message'],
            'upload_token' => $uploadEntry->upload_token
        ];
    }

    /**
     * Get the web api url for uploads.
     *
     * @param string|null $uploadToken
     * @return string
     */
    public function getWebUploadUrl(string $uploadToken = null): string
    {
        return $this->getWebApiUrl('video/upload/' . $uploadToken);
    }

    /**
     * Get the route for receiving an upload token.
     *
     * @return string
     */
    public function getUploadTokenRoute(): string
    {
        return route('transmorpherVideoToken');
    }

    /**
     * Get the max file size for uploads with dropzone.
     *
     * @return int
     */
    public function getMaxFileSize(): int
    {
        return config('transmorpher.dropzone_upload.video_max_file_size');
    }
}
