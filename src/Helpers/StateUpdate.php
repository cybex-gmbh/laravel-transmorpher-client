<?php

namespace Transmorpher\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Transmorpher\Enums\State;
use Transmorpher\Models\TransmorpherMedia;

class StateUpdate
{
    /**
     * Get the processing state of the latest upload.
     *
     * @param Request $request
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function getProcessingState(Request $request, TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        $latestUpload = $transmorpherMedia->TransmorpherUploads()->latest()->first();

        if ($request->input('upload_token') !== $transmorpherMedia->latest_upload_token) {
            $response = 'Canceled by a new upload.';
            $state = State::ERROR;
        }

        return response()->json(['response' => $response ?? $latestUpload->message, 'state' => $state ?? $latestUpload->state, 'url' => sprintf('%s?c=%s', $transmorpherMedia->getTransmorpher()->getMp4Url(), $latestUpload->updated_at)]);
    }

    /**
     * Return whether the latest upload is currently uploading or processing.
     *
     * @param Request $request
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function getUploadingState(Request $request, TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        $latestUpload = $transmorpherMedia->TransmorpherUploads()->latest()->first();

        return response()->json(['upload_in_process' => $latestUpload->state == State::INITIALIZING || $latestUpload->state == State::PROCESSING]);
    }
}
