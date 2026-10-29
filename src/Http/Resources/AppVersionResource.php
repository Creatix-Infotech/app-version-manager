<?php

namespace Creatix\AppVersionManager\Http\Resources;

use Creatix\AppVersionManager\Enums\Platform;
use Creatix\AppVersionManager\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read AppVersion $resource
 */
class AppVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $platform = $this->resource->platform instanceof Platform
            ? $this->resource->platform->value
            : (string) $this->resource->platform;

        $platformLabel = $this->resource->platform instanceof Platform
            ? $this->resource->platform->label()
            : ucfirst($platform);

        return [
            'id' => $this->resource->id,
            'platform' => $platform,
            'platform_label' => $platformLabel,
            'latest_version' => $this->resource->latest_version,
            'latest_build' => $this->resource->latest_build,
            'minimum_version' => $this->resource->minimum_version,
            'minimum_build' => $this->resource->minimum_build,
            'store_url' => $this->resource->store_url,
            'update_message' => $this->resource->update_message,
            'is_active' => (bool) $this->resource->is_active,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
