<?php

namespace Transmorpher\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Transmorpher\Models\TransmorpherMedia;
use Transmorpher\Models\TransmorpherUpload;

class UploadToken
{
    /**
     *
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function getUploadToken(TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        return response()->json($transmorpherMedia->getTopic()->reserveUploadSlot());
    }

    /**
     * @param Request $request
     * @param TransmorpherUpload $transmorpherUpload
     *
     * @return JsonResponse
     */
    public function handleUploadResponse(Request $request, TransmorpherUpload $transmorpherUpload): JsonResponse
    {
        return response()->json($transmorpherUpload->handleStateUpdate($request->input('response'), $request->input('http_code')));
    }
}
