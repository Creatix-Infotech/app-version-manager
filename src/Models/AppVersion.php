<?php

namespace Creatix\AppVersionManager\Models;

use Creatix\AppVersionManager\Enums\Platform;
use Creatix\AppVersionManager\Enums\UpdateRequirement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Version policy for a device type (platform): latest published build
 * and the minimum build required to operate.
 *
 * @property string $id
 * @property string|Platform $platform
 * @property string $latest_version
 * @property int $latest_build
 * @property string $minimum_version
 * @property int $minimum_build
 * @property string $store_url
 * @property string|null $update_message
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AppVersion extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('app-version-manager.table_name', parent::getTable());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'latest_build' => 'integer',
            'minimum_build' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $model) {
            $platform = $model->platform instanceof Platform ? $model->platform->value : (string) $model->platform;
            static::flushCacheFor($platform);
        });

        static::deleted(function (self $model) {
            $platform = $model->platform instanceof Platform ? $model->platform->value : (string) $model->platform;
            static::flushCacheFor($platform);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the active version policy currently enforced for a platform.
     */
    public static function currentFor(string|Platform $platform): ?self
    {
        $platformValue = $platform instanceof Platform ? $platform->value : $platform;

        $useCache = config('app-version-manager.cache.enabled', true);
        $ttl = config('app-version-manager.cache.ttl', 3600);
        $cacheKey = config('app-version-manager.cache.key_prefix', 'app_version_policy_').$platformValue;

        if ($useCache) {
            return Cache::remember($cacheKey, $ttl, function () use ($platformValue) {
                return static::fetchActivePolicy($platformValue);
            });
        }

        return static::fetchActivePolicy($platformValue);
    }

    protected static function fetchActivePolicy(string $platform): ?self
    {
        return static::query()
            ->active()
            ->where('platform', $platform)
            ->orderByDesc('latest_build')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Flush cache for a given platform.
     */
    public static function flushCacheFor(string|Platform $platform): void
    {
        $platformValue = $platform instanceof Platform ? $platform->value : $platform;
        $cacheKey = config('app-version-manager.cache.key_prefix', 'app_version_policy_').$platformValue;

        Cache::forget($cacheKey);
    }

    /**
     * Compute the update requirement for a given client build number.
     */
    public function requirementFor(int $build): UpdateRequirement
    {
        return match (true) {
            $build < $this->minimum_build => UpdateRequirement::FORCED,
            $build < $this->latest_build => UpdateRequirement::OPTIONAL,
            default => UpdateRequirement::NONE,
        };
    }
}
