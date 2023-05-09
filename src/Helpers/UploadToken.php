<?php

namespace Transmorpher\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Transmorpher\Models\TransmorpherMedia;

class UploadToken
{
    /**
     *
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function getUploadToken(TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        return response()->json($transmorpherMedia->getTransmorpher()->prepareUpload());
    }

    /**
     * @param Request $request
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function handleUploadResponse(Request $request, TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        return response()->json($transmorpherMedia->getTransmorpher()->handleUploadResponse(
            $request->input('response'),
            $transmorpherMedia->TransmorpherUploads()->whereToken($request->input('upload_token'))->first())
        );
    }
}
