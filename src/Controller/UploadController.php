<?php

namespace Transmorpher\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Transmorpher\Enums\UploadState;
use Transmorpher\Models\TransmorpherMedia;
use Transmorpher\Models\TransmorpherUpload;

class UploadController
{
    public function getUploadToken(Request $request, TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        return response()->json($transmorpherMedia->getMedia()->reserveUploadSlot($request->filename));
    }

    public function handleUploadResponse(Request $request, TransmorpherUpload $transmorpherUpload): JsonResponse
    {
        return response()->json($transmorpherUpload->handleStateUpdate($request->input('response'), $request->input('http_code')));
    }

    public function getChunkUploadUrl(TransmorpherUpload $transmorpherUpload, int $chunkNumber): JsonResponse
    {
        return response()->json($transmorpherUpload->TransmorpherMedia->getMedia()->getChunkUploadUrl($transmorpherUpload, $chunkNumber));
    }

    public function completeUpload(Request $request, TransmorpherUpload $transmorpherUpload): JsonResponse
    {
        return response()->json($transmorpherUpload->TransmorpherMedia->getMedia()->completeUpload($transmorpherUpload));
    }

    public function abortUpload(Request $request, TransmorpherUpload $transmorpherUpload): JsonResponse
    {
        if ($transmorpherUpload->state !== UploadState::INITIALIZING && $transmorpherUpload->state !== UploadState::UPLOADING) {
            return response()->json(trans('transmorpher::errors.cannot_abort_finished_upload'), 400);
        }

        return response()->json($transmorpherUpload->TransmorpherMedia->getMedia()->abortUpload($transmorpherUpload));
    }
}
