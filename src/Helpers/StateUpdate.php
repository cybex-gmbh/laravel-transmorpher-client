<?php

namespace Transmorpher\Helpers;

use Illuminate\Http\JsonResponse;
use Transmorpher\Models\TransmorpherMedia;

class StateUpdate
{
    /**
     * Handle the callback from the Transmorpher.
     *
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function __invoke(TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        $latestProtocol = $transmorpherMedia->TransmorpherProtocols()->latest()->first();
        return response()->json(['state' => $latestProtocol->state, 'url' => sprintf('%s?c=%s', $transmorpherMedia->getTransmorpher()->getMp4Url(), $latestProtocol->updated_at)]);
    }
}
