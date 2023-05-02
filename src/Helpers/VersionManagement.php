<?php

namespace Transmorpher\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Transmorpher\Models\TransmorpherMedia;

class VersionManagement
{
    /**
     * @param Request $request
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function getVersions(Request $request, TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        return response()->json($transmorpherMedia->getTransmorpher()->getVersions());
    }

    public function setVersion(Request $request, TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        return response()->json($transmorpherMedia->getTransmorpher()->setVersion($request->input('version')));
    }

    public function delete(Request $request, TransmorpherMedia $transmorpherMedia)
    {
        $transmorpherMedia = TransmorpherMedia::find($request->input('transmorpher_media_key'));
    }
}