<?php

namespace Transmorpher\Helpers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Transmorpher\Enums\State;
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
        if (! $verifiedRequest = sodium_crypto_sign_open(sodium_hex2bin($request->get('signed_response')), Http::get(sprintf('%s/publickey', config('transmorpher.api.s2s_url'))))) {
            return response()->noContent(403);
        }

        $body = json_decode($verifiedRequest, true);
        $uploadEntry = TransmorpherUpload::whereUploadToken($body['upload_token'])->first();
        $transmorpherMedia = $uploadEntry->TransmorpherMedia;

        if ($body['success']) {
            $transmorpherMedia->update(['is_ready' => 1, 'public_path' => $body['public_path'], 'last_response' => State::SUCCESS]);
            $uploadEntry->update(['state' => State::SUCCESS, 'message' => $body['response']]);
        } else {
            $transmorpherMedia->update(['last_response' => State::ERROR]);
            $uploadEntry->update(['state' => State::ERROR, 'message' => $body['response']]);
        }

        return response()->noContent(200);
    }
}
