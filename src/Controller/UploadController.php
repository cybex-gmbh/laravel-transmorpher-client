<?php

namespace Transmorpher\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Transmorpher\Models\TransmorpherMedia;
use Transmorpher\Models\TransmorpherUpload;

class UploadController
{
    /**
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function getUploadToken(TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        return response()->json($transmorpherMedia->getMedia()->reserveUploadSlot());
    }

    /**
     * @param Request $request
     * @param TransmorpherUpload $transmorpherUpload
     *
     * @return JsonResponse
     */
    public function handleUploadResponse(Request $request, TransmorpherUpload $transmorpherUpload): JsonResponse
    {
        return response()->json(array_merge(
            $transmorpherUpload->handleStateUpdate($request->input('response'), $request->input('http_code')),
            $transmorpherUpload->TransmorpherMedia->getMedia()->getMediaUrls()
        ));
    }
}
