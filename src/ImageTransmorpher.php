<?php

namespace Cybex\Transmorpher;

use Cybex\Transmorpher\Models\MediaUpload;
use Cybex\Transmorpher\Models\MediaUploadProtocol;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImageTransmorpher
{
    protected MediaUpload $mediaUpload;
    protected MediaUploadProtocol $protocolEntry;

    public function __construct(protected HasMediaUploadsInterface $model, protected string $differentiator)
    {
        $mediaUploads = $model->MediaUploads();

        $this->mediaUpload = $mediaUploads->whereDifferentiator($differentiator)->first() ?? $mediaUploads->create(['differentiator' => $differentiator, 'id_token' => uniqid()]);
    }

    public function upload(): array
    {
        $response = $this->makeApiRequest('/image/upload', 'post', ['identifier' => $this->getIdentifier()]);
        $body = json_decode($response->body(), true);

        $this->protocolEntry->update($body['success']
            ? ['state' => State::SUCCESS, 'public_path' => $body['public_path']]
            : ['state' => State::ERROR]
        );

        return $body;
    }

    public function uploadVideo(): array
    {
        // TODO put this in VideoTransmorpher, also put stuff which both use in abstract Transmorpher class.
        $response = $this->makeApiRequest('/video/upload', 'post', [
            'identifier'   => $this->getIdentifier(),
            'id_token'     => $this->mediaUpload->id_token,
//            'callback_url' => route('transmorpherCallback'),
            'callback_url' => 'http://commander/transmorpher/callback',
        ]);

        $body = json_decode($response->body(), true);

        $this->protocolEntry->update($body['success']
            ? ['state' => State::PROCESSING]
            : ['state' => State::ERROR]
        );

        return $body;
    }

    public function delete(): array
    {
        $response = $this->makeApiRequest(sprintf('/media/%s', $this->getIdentifier()), 'delete');
        $body = json_decode($response->body(), true);

        if (!$body['success']) {
            $this->protocolEntry->update(['state' => State::ERROR]);
        }

        return $body;
    }

    public function setVersion($versionNumber): array
    {
        $response = $this->makeApiRequest(sprintf('/media/%s/version/%s/set', $this->getIdentifier(), $versionNumber), 'patch');
        $body = json_decode($response->body(), true);

        $this->protocolEntry->update($body['success']
            ? ['state' => State::SUCCESS, 'public_path' => $body['public_path']]
            : ['state' => State::ERROR]
        );

        return $body;
    }

    public function getVersions(): array
    {
        return json_decode($this->makeApiRequest(sprintf('/media/%s/versions', $this->getIdentifier()), 'get')->body(), true);
    }

    public function getUrl(array $transformations = []): string
    {
        return sprintf('%s/%s/%s',
            $this->getPublicUrl(),
            $this->mediaUpload->MediaUploadProtocols()->whereState(State::SUCCESS)->latest()->first()->public_path,
            $this->getTransformations($transformations)
        );
    }

    public function getOriginal(int $versionNumber): string
    {
        return $this->makeApiRequest(sprintf('/image/%s/version/%s', $this->getIdentifier(), $versionNumber), 'get')->body();
    }

    public function getDerivative(array $transformations = []): string
    {
        return Http::get($this->getUrl($transformations))->body();
    }

    protected function makeApiRequest(string $route, string $method, array $body = []): Response
    {
        if ($method !== 'get') {
            $this->protocolEntry = $this->mediaUpload->MediaUploadProtocols()->create(['state' => State::PROCESSING]);
        }

        // TODO Request returnen und dann selbst posten und attachen

        return Http::withToken($this->getAuthToken())
            ->withOptions(['stream' => true])
            ->withHeaders(['Accept' => 'application/json'])
            ->attach('video', Storage::readStream('sample-video.mp4'))
            ->withoutRedirecting()->$method($this->getApiUrl() . $route, $body);
    }

    protected function getClientName(): string
    {
        return config('transmorpher.client_name');
    }

    protected function getAuthToken(): string
    {
        return config('transmorpher.api.auth_token');
    }

    protected function getApiUrl(): string
    {
        return config('transmorpher.api.url');
    }

    protected function getPublicUrl(): string
    {
        return config('transmorpher.public.url');
    }

    protected function getIdentifier(): string
    {
        return sprintf('%s-%s-%s', $this->mediaUpload->differentiator, $this->mediaUpload->uploadable_type, $this->mediaUpload->uploadable_id);
    }

    protected function getTransformations(array $transformations): string
    {
        foreach ($transformations as $transformation => $value) {
            match ($transformation) {
                'width'   => $transformationParts[] = sprintf('w-%d', $value),
                'height'  => $transformationParts[] = sprintf('h-%d', $value),
                'format'  => $transformationParts[] = sprintf('f-%s', $value),
                'quality' => $transformationParts[] = sprintf('q-%d', $value),
                default   => null
            };
        }

        return implode('+', $transformationParts ?? []);
    }
}
