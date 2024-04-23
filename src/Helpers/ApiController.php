<?php

namespace Transmorpher\Helpers;

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

    protected function verifySignature(Request $notification): bool|string
    {
        return sodium_crypto_sign_open(sodium_hex2bin($notification->get('signed_notification')), Http::get(TransmorpherApi::S2S->getUrl('publickey')));
    }

    protected function handleVideoTranscodingNotification(array $videoTranscodingResult): void
    {
        // If we have no upload for the provided token, the derivatives were purged on the media server, and we should update the hash of the latest upload.
        $upload = TransmorpherUpload::whereToken($videoTranscodingResult['upload_token'])->first()
            ?? TransmorpherMedia::fromIdentifier($videoTranscodingResult['identifier'])->latestSuccessfulUpload;

        if ($videoTranscodingResult['state'] !== UploadState::ERROR->value) {
            $upload->TransmorpherMedia->update(['is_ready' => 1, 'public_path' => $videoTranscodingResult['public_path'], 'hash' => $videoTranscodingResult['hash']]);
        }

        $upload->update(['state' => $videoTranscodingResult['state'], 'message' => $videoTranscodingResult['message']]);
    }

    protected function handleCacheInvalidationNotification(array $cacheInvalidationNotification): void
    {
        Cache::put('cache_invalidation_revision', $cacheInvalidationNotification['cache_invalidation_revision'], now()->addDays(14));

    }
}
