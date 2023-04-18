<?php

namespace Transmorpher\Helpers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Transmorpher\Models\TransmorpherMedia;

class UploadToken
{
    /**
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getImageUploadToken(Request $request): JsonResponse
    {
        $imageTransmorpher = TransmorpherMedia::findOrFail($request->input('transmorpher_media_key'))->getTransmorpher();

        return response()->json($imageTransmorpher->prepareUpload());
    }

    /**
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getVideoUploadToken(Request $request): JsonResponse
    {
        $videoTransmorpher = TransmorpherMedia::findOrFail($request->input('transmorpher_media_key'))->getTransmorpher();

        return response()->json($videoTransmorpher->prepareUpload());
    }

    public function handleUploadResponse(Request $request): JsonResponse
    {
        $transmorpherMedia = TransmorpherMedia::findOrFail($request->input('transmorpher_media_key'));
        $transmorpher = $transmorpherMedia->getTransmorpher();
        $protocolEntry = $transmorpherMedia->ProtocolEntries()->whereIdToken($request->id_token);

        return response()->json($transmorpher->handleUploadResponse(json_decode($request->input('response')), $protocolEntry));
    }
}
