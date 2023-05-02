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
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function __invoke(Request $request, TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        $response = 'processing';
        $latestProtocol = $transmorpherMedia->TransmorpherProtocols()->latest()->first();
        $state = $latestProtocol->state;

        if ($request->input('upload_token') !== $transmorpherMedia->last_upload_token) {
            $response = 'Upload slot was overwritten by a new upload.';
            $state = State::DELETED;
        }

        return response()->json(['response' => $response, 'state' => $state, 'url' => sprintf('%s?c=%s', $transmorpherMedia->getTransmorpher()->getMp4Url(), $latestProtocol->updated_at)]);
    }
}
