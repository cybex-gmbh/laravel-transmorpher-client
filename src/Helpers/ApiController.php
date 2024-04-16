<?php

namespace Transmorpher\Helpers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Transmorpher\Enums\ServerNotification;
use Transmorpher\Enums\TransmorpherApi;
use Transmorpher\Enums\UploadState;
use Transmorpher\Exceptions\UnknownServerNotificationException;
use Transmorpher\Models\TransmorpherUpload;

class ApiController
{
    /**
     * Handle the notification from the Transmorpher.
     *
     * @param Request $request
     *
     * @return Response
     * @throws UnknownServerNotificationException
     */
    public function __invoke(Request $request): Response
    {
        if (!$verifiedRequest = sodium_crypto_sign_open(sodium_hex2bin($request->get('signed_response')), Http::get(TransmorpherApi::S2S->getUrl('publickey')))) {
            return response()->noContent(403);
        }

        $serverNotification = json_decode($verifiedRequest, true);

        match ($serverNotification['type']) {
            ServerNotification::VIDEO_TRANSCODING->value => $this->handleVideoTranscodingNotification($serverNotification),
            default => throw new UnknownServerNotificationException($serverNotification['type'])
        };

        return response()->noContent(200);
    }

    protected function handleVideoTranscodingNotification(array $videoTranscodingResult): void
    {
        $upload = TransmorpherUpload::whereToken($videoTranscodingResult['upload_token'])->first();

        if ($videoTranscodingResult['state'] !== UploadState::ERROR->value) {
            $upload->TransmorpherMedia->update(['is_ready' => 1, 'public_path' => $videoTranscodingResult['public_path'], 'hash' => $videoTranscodingResult['hash']]);
        }

        $upload->update(['state' => $videoTranscodingResult['state'], 'message' => $videoTranscodingResult['message']]);
    }
}
