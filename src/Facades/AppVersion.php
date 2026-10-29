<?php

namespace Creatix\AppVersionManager\Facades;

use Creatix\AppVersionManager\Services\AppVersionManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Creatix\AppVersionManager\Models\AppVersion|null currentFor(string|\Creatix\AppVersionManager\Enums\Platform $platform)
 * @method static array check(string|\Creatix\AppVersionManager\Enums\Platform $platform, int $clientBuild)
 * @method static array|null prefillFor(string|\Creatix\AppVersionManager\Enums\Platform|null $platform = null)
 * @method static string bumpVersion(string $version)
 *
 * @see \Creatix\AppVersionManager\Services\AppVersionManager
 */
class AppVersion extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AppVersionManager::class;
    }
}
