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
        $latestUpload = $transmorpherMedia->TransmorpherUploads()->latest()->first();

        if ($request->input('upload_token') !== $transmorpherMedia->last_upload_token) {
            $response = 'Canceled by a new upload.';
            $state = State::ERROR;
        }

        return response()->json(['response' => $response ?? $latestUpload->message, 'state' => $state ?? $latestUpload->state, 'url' => sprintf('%s?c=%s', $transmorpherMedia->getTransmorpher()->getMp4Url(), $latestUpload->updated_at)]);
    }
}
