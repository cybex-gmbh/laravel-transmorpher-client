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
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function __invoke(Request $request, TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        $latestProtocol = $transmorpherMedia->TransmorpherProtocols()->latest()->first();

        if ($request->input('upload_token') !== $transmorpherMedia->last_upload_token) {
            $response = 'Canceled by a new upload.';
            $state = State::DELETED;
        }

        return response()->json(['response' => $response ?? $latestProtocol->message, 'state' => $state ?? $latestProtocol->state, 'url' => sprintf('%s?c=%s', $transmorpherMedia->getTransmorpher()->getMp4Url(), $latestProtocol->updated_at)]);
    }
}
