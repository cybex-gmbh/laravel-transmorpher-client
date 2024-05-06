<?php

namespace Transmorpher\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Transmorpher\Enums\ServerNotification;
use Transmorpher\Enums\TransmorpherApi;
use Transmorpher\Enums\UploadState;
use Transmorpher\Exceptions\UnknownServerNotificationException;
use Transmorpher\Models\TransmorpherMedia;
use Transmorpher\Models\TransmorpherUpload;

class ApiController
{
    /**
     * Handle the notification from the Transmorpher.
     *
     * @param Request $notification
     *
     * @return Response
     * @throws UnknownServerNotificationException
     */
    public function __invoke(Request $notification): Response
    {
        $verifiedNotification = $this->verifySignature($notification);

        if (!$verifiedNotification) {
            return response()->noContent(403);
        }

        $serverNotification = json_decode($verifiedNotification, true);

        match ($serverNotification['notification_type']) {
            ServerNotification::VIDEO_TRANSCODING->value => $this->handleVideoTranscodingNotification($serverNotification),
            ServerNotification::CACHE_INVALIDATION->value => $this->handleCacheInvalidationNotification($serverNotification),
            default => throw new UnknownServerNotificationException($serverNotification['notification_type'])
        };

        return response()->noContent(200);
    }

    /**
     * Verifies the signature of an incoming request with the public key of the Transmorpher media server.
     * This ensures that this public endpoint only accepts requests from the Transmorpher media server.
     *
     * @param Request $notification
     * @return bool|string
     */
    protected function verifySignature(Request $notification): bool|string
    {
        return sodium_crypto_sign_open(sodium_hex2bin($notification->get('signed_notification')), Http::get(TransmorpherApi::S2S->getUrl('publickey')));
    }

    /**
     * Updates models with information provided after a video was transcoded by the Transmorpher media server.
     *
     * @param array $videoTranscodingResult
     * @return void
     */
    protected function handleVideoTranscodingNotification(array $videoTranscodingResult): void
    {
        // If we receive a transcoding result, but we don't know the upload token, that means that the server has purged and recreated the derivatives. 
        // Therefore we need to update the media hash on our side for cache busting.
        $upload = TransmorpherUpload::whereToken($videoTranscodingResult['upload_token'])->first()
            ?? TransmorpherMedia::fromIdentifier($videoTranscodingResult['identifier'])->latestSuccessfulUpload;

        if ($videoTranscodingResult['state'] !== UploadState::ERROR->value) {
            $upload->TransmorpherMedia->update(['is_ready' => 1, 'public_path' => $videoTranscodingResult['public_path'], 'hash' => $videoTranscodingResult['hash']]);
        }

        $upload->update(['state' => $videoTranscodingResult['state'], 'message' => $videoTranscodingResult['message']]);
    }

    /**
     * Updates the cached cache invalidator.
     * Clients will then receive new cache buster values when requesting derivatives.
     *
     * @param array $cacheInvalidationNotification
     * @return void
     */
    protected function handleCacheInvalidationNotification(array $cacheInvalidationNotification): void
    {
        Cache::put('cache_invalidator', $cacheInvalidationNotification['cache_invalidator'], now()->addDays(14));
    }
}
