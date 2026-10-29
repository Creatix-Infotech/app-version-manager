<?php

namespace Creatix\AppVersionManager\Http\Resources;

use Creatix\AppVersionManager\Enums\Platform;
use Creatix\AppVersionManager\Enums\UpdateRequirement;
use Creatix\AppVersionManager\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read AppVersion $resource
 */
class AppVersionStatusResource extends JsonResource
{
    public function __construct(
        AppVersion $resource,
        protected UpdateRequirement $requirement,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $platform = $this->resource->platform instanceof Platform
            ? $this->resource->platform->value
            : (string) $this->resource->platform;

        return [
            'platform' => $platform,
            'update_required' => $this->requirement->value,
            'is_supported' => $this->requirement->isSupported(),
            'minimum_version' => $this->resource->minimum_version,
            'minimum_build' => $this->resource->minimum_build,
            'latest_version' => $this->resource->latest_version,
            'latest_build' => $this->resource->latest_build,
            'store_url' => $this->resource->store_url,
            'update_message' => $this->resource->update_message ?: config('app-version-manager.defaults.update_message'),
        ];
    }
}

