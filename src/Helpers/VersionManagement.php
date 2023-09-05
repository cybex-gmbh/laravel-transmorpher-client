<?php

namespace Transmorpher\Helpers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        return response()->json(
            array_merge(
                $transmorpherMedia->getTopic()->getVersions(), [
                    'thumbnailUrl' => sprintf('%s?c=%s', $transmorpherMedia->getTopic()->getThumbnailUrl(), $transmorpherMedia->updated_at),
                    'publicPath' => $transmorpherMedia->public_path,
                ]
            )
        );
    }

    /**
     * @param Request $request
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function setVersion(Request $request, TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        return response()->json($transmorpherMedia->getTopic()->setVersion($request->input('version')));
    }

    /**
     * @param Request $request
     * @param TransmorpherMedia $transmorpherMedia
     * @return JsonResponse
     */
    public function delete(Request $request, TransmorpherMedia $transmorpherMedia): JsonResponse
    {
        return response()->json($transmorpherMedia->getTopic()->delete());
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
        $response = $transmorpherMedia->getTopic()->getOriginal($version);

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
        $response = $transmorpherMedia->getTopic()->getDerivativeForVersion($version, $transformations);

        return response($response['binary'], 200, ['Content-Type' => $response['mimetype']]);
    }
}
