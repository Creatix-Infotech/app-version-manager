<?php

namespace Creatix\AppVersionManager\Http\Middleware;

use Closure;
use Creatix\AppVersionManager\Enums\Platform;
use Creatix\AppVersionManager\Enums\UpdateRequirement;
use Creatix\AppVersionManager\Facades\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks API requests from a mobile client running below the minimum supported version.
 *
 * Checks `X-App-Platform` and `X-App-Build` headers. If the client build is below the
 * active minimum, returns a 426 Upgrade Required JSON response.
 */
class EnforceMinimumAppVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $platformHeader = config('app-version-manager.headers.platform', 'X-App-Platform');
        $buildHeader = config('app-version-manager.headers.build', 'X-App-Build');

        $platform = $request->header($platformHeader);
        $build = $request->header($buildHeader);

        if (blank($platform) || ! is_numeric($build)) {
            return $next($request);
        }

        $policy = AppVersion::currentFor($platform);

        if ($policy && $policy->requirementFor((int) $build) === UpdateRequirement::FORCED) {
            $platformVal = $policy->platform instanceof Platform ? $policy->platform->value : (string) $policy->platform;

            return new JsonResponse([
                'success' => false,
                'message' => 'A newer version of the app is required to continue.',
                'code' => 426,
                'data' => [
                    'platform' => $platformVal,
                    'update_required' => UpdateRequirement::FORCED->value,
                    'minimum_version' => $policy->minimum_version,
                    'minimum_build' => $policy->minimum_build,
                    'latest_version' => $policy->latest_version,
                    'latest_build' => $policy->latest_build,
                    'store_url' => $policy->store_url,
                    'update_message' => $policy->update_message ?: config('app-version-manager.defaults.update_message'),
                ],
            ], 426);
        }

        return $next($request);
    }
}

