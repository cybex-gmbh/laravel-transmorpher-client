<?php

namespace Transmorpher\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Transmorpher\Enums\State;
use Transmorpher\Models\TransmorpherMedia;

class StateUpdate
{
    /**
     * Handle the callback from the Transmorpher.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $transmorpherMedia = TransmorpherMedia::find($request->input('transmorpher_media_key'));

        $latestProtocol = $transmorpherMedia->TransmorpherProtocols()->where('state', '!=', State::ERROR)->latest()->first();
        return response()->json(['state' => $latestProtocol->state, 'url' => sprintf('%s?c=%s', $transmorpherMedia->getTransmorpher()->getMp4Url(), $latestProtocol->updated_at)]);
    }
}
