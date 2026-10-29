<?php

namespace Creatix\AppVersionManager\Services;

use Creatix\AppVersionManager\Enums\Platform;
use Creatix\AppVersionManager\Enums\UpdateRequirement;
use Creatix\AppVersionManager\Models\AppVersion;

class AppVersionManager
{
    /**
     * Get the active version policy for a platform.
     */
    public function currentFor(string|Platform $platform): ?AppVersion
    {
        $modelClass = config('app-version-manager.model', AppVersion::class);

        return $modelClass::currentFor($platform);
    }

    /**
     * Check update status for a platform and build number.
     *
     * @return array{
     *     platform: string,
     *     update_required: string,
     *     is_supported: bool,
     *     latest_version: string|null,
     *     latest_build: int|null,
     *     minimum_version: string|null,
     *     minimum_build: int|null,
     *     store_url: string|null,
     *     update_message: string|null
     * }
     */
    public function check(string|Platform $platform, int $clientBuild): array
    {
        $platformValue = $platform instanceof Platform ? $platform->value : $platform;
        $policy = $this->currentFor($platformValue);

        if (! $policy) {
            return [
                'platform' => $platformValue,
                'update_required' => UpdateRequirement::NONE->value,
                'is_supported' => true,
                'latest_version' => null,
                'latest_build' => null,
                'minimum_version' => null,
                'minimum_build' => null,
                'store_url' => null,
                'update_message' => null,
            ];
        }

        $requirement = $policy->requirementFor($clientBuild);

        return [
            'platform' => $policy->platform instanceof Platform ? $policy->platform->value : (string) $policy->platform,
            'update_required' => $requirement->value,
            'is_supported' => $requirement->isSupported(),
            'latest_version' => $policy->latest_version,
            'latest_build' => $policy->latest_build,
            'minimum_version' => $policy->minimum_version,
            'minimum_build' => $policy->minimum_build,
            'store_url' => $policy->store_url,
            'update_message' => $policy->update_message ?: config('app-version-manager.defaults.update_message'),
        ];
    }

    /**
     * Get pre-filled next version values for a platform (auto-incrementing latest version & build).
     *
     * @return array{
     *     platform: string|null,
     *     latest_version: string,
     *     latest_build: int,
     *     minimum_version: string,
     *     minimum_build: int,
     *     store_url: string,
     *     update_message: string|null,
     *     is_active: bool
     * }|null
     */
    public function prefillFor(string|Platform|null $platform = null): ?array
    {
        $modelClass = config('app-version-manager.model', AppVersion::class);

        return $modelClass::prefillFor($platform);
    }

    /**
     * Increment the last numeric segment of a version string.
     */
    public function bumpVersion(string $version): string
    {
        $modelClass = config('app-version-manager.model', AppVersion::class);

        return $modelClass::bumpVersion($version);
    }
}
