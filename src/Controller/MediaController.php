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
     * @param Request $request
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function getVersions(Request $request, TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        return response()->json(
            array_merge($transmorpherMedia->getMedia()->getVersions(), $transmorpherMedia->getMedia()->getMediaUrls())
        );
    }

    /**
     * @param Request $request
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function setVersion(Request $request, TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        return response()->json(
            array_merge($transmorpherMedia->getMedia()->setVersion($request->input('version')), $transmorpherMedia->getMedia()->getMediaUrls())
        );
    }

    /**
     * @param Request $request
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function delete(Request $request, TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        return response()->json(array_merge($transmorpherMedia->getMedia()->delete(), [
            'placeholderUrl' => $transmorpherMedia->getMedia()->getPlaceholderUrl()
        ]));
    }

    /**
     * @param Request $request
     * @param TransmorpherMedia $transmorpherMedia
     * @param int $version
     *
     * @return Application|ResponseFactory|Response
     */
    public function getOriginal(Request $request, TransmorpherMedia $transmorpherMedia, int $version): Response|Application|ResponseFactory
    {
        $response = $transmorpherMedia->getMedia()->getOriginal($version);

        return response($response['binary'], 200, ['Content-Type' => $response['mimetype']]);
    }

    /**
     * @param Request $request
     * @param TransmorpherMedia $transmorpherMedia
     * @param int $version
     * @param string $transformations
     *
     * @return Application|ResponseFactory|Response
     */
    public function getDerivativeForVersion(Request $request, TransmorpherMedia $transmorpherMedia, int $version, string $transformations = ''): Response|Application|ResponseFactory
    {
        $response = $transmorpherMedia->getMedia()->getDerivativeForVersion($version, $transformations);

        return response($response['binary'], 200, ['Content-Type' => $response['mimetype']]);
    }
}
