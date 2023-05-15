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
        return response()->json($transmorpherMedia->getTransmorpher()->reserveUploadSlot());
    }

    /**
     * @param Request  $request
     * @param TransmorpherMedia $transmorpherMedia
     * @param TransmorpherUpload $transmorpherUpload
     *
     * @return JsonResponse
     */
    public function handleUploadResponse(Request $request, TransmorpherMedia $transmorpherMedia, TransmorpherUpload $transmorpherUpload): JsonResponse
    {
        return response()->json($transmorpherMedia->getTransmorpher()->handleUploadResponse(
            $request->input('response'),
            $transmorpherUpload)
        );
    }
}
