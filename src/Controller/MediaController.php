<?php

namespace Transmorpher\Controller;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Transmorpher\Models\TransmorpherMedia;

class MediaController
{
    /**
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function getVersions(TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        return response()->json(($transmorpherMedia->getMedia()->getVersions()));
    }

    /**
     * @param Request $request
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function setVersion(Request $request, TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        return response()->json($transmorpherMedia->getMedia()->setVersion($request->input('version')));
    }

    /**
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function delete(TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        return response()->json($transmorpherMedia->getMedia()->delete());
    }

    /**
     * @param TransmorpherMedia $transmorpherMedia
     * @param int $version
     *
     * @return Application|ResponseFactory|Response
     */
    public function getOriginal(TransmorpherMedia $transmorpherMedia, int $version): Response|Application|ResponseFactory
    {
        $response = $transmorpherMedia->getMedia()->getOriginal($version);

        return response($response['binary'], 200, ['Content-Type' => $response['mimetype']]);
    }

    /**
     * @param TransmorpherMedia $transmorpherMedia
     * @param int $version
     * @param string $transformations
     *
     * @return Application|ResponseFactory|Response
     */
    public function getDerivativeForVersion(TransmorpherMedia $transmorpherMedia, int $version, string $transformations = ''): Response|Application|ResponseFactory
    {
        $response = $transmorpherMedia->getMedia()->getDerivativeForVersion($version, $transformations);

        return response($response['binary'], 200, ['Content-Type' => $response['mimetype']]);
    }
}
