<?php

namespace Creatix\AppVersionManager\Http\Controllers;

use Creatix\AppVersionManager\Enums\UpdateRequirement;
use Creatix\AppVersionManager\Facades\AppVersion;
use Creatix\AppVersionManager\Http\Requests\AppVersionCheckRequest;
use Creatix\AppVersionManager\Http\Resources\AppVersionStatusResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AppVersionApiController extends Controller
{
    /**
     * Check version status for a given mobile client.
     */
    public function __invoke(AppVersionCheckRequest $request): JsonResponse
    {
        $platform = (string) $request->input('platform');
        $build = (int) $request->input('build');

        $policy = AppVersion::currentFor($platform);

        if (! $policy) {
            return new JsonResponse([
                'success' => true,
                'message' => 'No version policy configured for this platform.',
                'data' => [
                    'platform' => $platform,
                    'update_required' => UpdateRequirement::NONE->value,
                    'is_supported' => true,
                ],
            ]);
        }

        $requirement = $policy->requirementFor($build);

        return new JsonResponse([
            'success' => true,
            'message' => 'App version status retrieved successfully.',
            'data' => (new AppVersionStatusResource($policy, $requirement))->resolve(),
        ]);
    }
}

