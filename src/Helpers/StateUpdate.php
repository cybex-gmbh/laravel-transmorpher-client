<?php

namespace Transmorpher\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Transmorpher\Enums\State;
use Transmorpher\Models\TransmorpherMedia;
use Transmorpher\Models\TransmorpherUpload;

class StateUpdate
{
    /**
     * Get the processing state of the latest upload.
     *
     * @param Request $request
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function getState(Request $request, TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        $latestUpload = $transmorpherMedia->TransmorpherUploads()->latest()->first();


        // If no upload token was provided, return information for latest upload.
        if ($request->input('upload_token') && $request->input('upload_token') !== $transmorpherMedia->latest_upload_token) {
            $message = 'Canceled by a new upload.';
            $state = State::ERROR;
        }

        return response()->json([
            'clientMessage' => $message ?? $latestUpload?->message,
            'state' => $state ?? $latestUpload?->state,
            'thumbnailUrl' => sprintf('%s?c=%s', $transmorpherMedia->getTransmorpher()->getThumbnailUrl(), $latestUpload?->updated_at),
            'fullsizeUrl' => sprintf('%s?c=%s', $transmorpherMedia->getTransmorpher()->getUrl(), $latestUpload?->updated_at),
            'latestUploadToken' => $transmorpherMedia->latest_upload_token,
            'lastUpdated' => $latestUpload?->updated_at
        ]);
    }

    public function setUploadingState(Request $request, TransmorpherUpload $transmorpherUpload): JsonResponse
    {
        $transmorpherUpload->update(['state' => State::UPLOADING, 'message' => 'Upload has started.']);

        return response()->json(['state' => $transmorpherUpload->state]);
    }
}
