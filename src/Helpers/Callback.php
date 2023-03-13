<?php

namespace Transmorpher\Helpers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Transmorpher\Enums\State;
use Transmorpher\Models\TransmorpherProtocol;

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
        if (! $verifiedRequest = sodium_crypto_sign_open(sodium_hex2bin($request->get('signed_response')), Http::get(sprintf('%s/publickey', config('transmorpher.api.url'))))) {
            return response()->noContent(403);
        }

        $body              = json_decode($verifiedRequest, true);
        $protocolEntry     = TransmorpherProtocol::whereIdToken($body['id_token'])->first();
        $transmorpherMedia = $protocolEntry->TransmorpherMedia;

        if ($body['success']) {
            $transmorpherMedia->update(['is_ready' => 1, 'public_path' => $body['public_path'], 'last_response' => State::SUCCESS]);
            $protocolEntry->update(['state' => State::SUCCESS]);
        } else {
            $transmorpherMedia->update(['last_response' => State::ERROR]);
            $protocolEntry->update(['state' => State::ERROR, 'message' => $body['response']]);
        }

        return response()->noContent(200);
    }
}