<?php

namespace Transmorpher\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Transmorpher\Enums\UploadState;
use Transmorpher\Models\TransmorpherMedia;
use Transmorpher\Models\TransmorpherUpload;

class UploadStateController
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

        // If no upload token was provided, return information for the latest upload.
        if ($request->input('upload_token') && $request->input('upload_token') !== $transmorpherMedia->latest_upload_token) {
            $message = trans('transmorpher::errors.upload_canceled_or_took_too_long');
            $state = UploadState::ERROR->value;
        }

        if (($state ?? $latestUpload?->state->value) === UploadState::DELETED->value) {
            $mediaUrls = ['placeholderUrl' => $transmorpherMedia->getMedia()->getPlaceholderUrl()];
        } else {
            $mediaUrls = $transmorpherMedia->getMedia()->getMediaUrls();
        }

        return response()->json([
            'clientMessage' => $message ?? $latestUpload?->message,
            'state' => $state ?? $latestUpload?->state,
            'latestUploadToken' => $transmorpherMedia->latest_upload_token,
            'lastUpdated' => $latestUpload?->updated_at,
            ...$mediaUrls
        ]);
    }

    public function setUploadingState(Request $request, TransmorpherUpload $transmorpherUpload): JsonResponse
    {
        $transmorpherUpload->update(['state' => UploadState::UPLOADING, 'message' => 'Upload has started.']);

        return response()->json(['state' => $transmorpherUpload->state]);
    }
}
