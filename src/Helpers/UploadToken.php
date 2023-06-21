<?php

namespace Transmorpher\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Transmorpher\Enums\UploadState;
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
     * @param Request $request
     * @param TransmorpherUpload $transmorpherUpload
     *
     * @return JsonResponse
     */
    public function handleUploadResponse(Request $request, TransmorpherUpload $transmorpherUpload): JsonResponse
    {
        // Errors directly returned from dropzone are strings.
        $response = is_array($request->input('response')) ? $request->input('response') : ['state' => UploadState::ERROR->value, 'clientMessage' => $request->input('response'), 'message' => $request->input('response')];

        return response()->json($transmorpherUpload->complete($response, $request->input('http_code')));
    }
}
