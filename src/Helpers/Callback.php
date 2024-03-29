<?php

namespace Transmorpher\Helpers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Transmorpher\Enums\TransmorpherApi;
use Transmorpher\Enums\UploadState;
use Transmorpher\Models\TransmorpherUpload;

class Callback
{
    /**
     * Handle the callback from the Transmorpher.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function __invoke(Request $request): Response
    {
        if (!$verifiedRequest = sodium_crypto_sign_open(sodium_hex2bin($request->get('signed_response')), Http::get(TransmorpherApi::S2S->getUrl('publickey')))) {
            return response()->noContent(403);
        }

        $body = json_decode($verifiedRequest, true);
        $upload = TransmorpherUpload::whereToken($body['upload_token'])->first();

        if ($body['state'] !== UploadState::ERROR->value) {
            $upload->TransmorpherMedia->update(['is_ready' => 1, 'public_path' => $body['public_path']]);
        }

        $upload->update(['state' => $body['state'], 'message' => $body['message']]);

        return response()->noContent(200);
    }
}
