<?php

namespace Transmorpher\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        return response()->json(['state' => $transmorpherMedia->TransmorpherProtocols()->latest()->first()->state]);
    }
}