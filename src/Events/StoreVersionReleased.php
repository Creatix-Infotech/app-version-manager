<?php

namespace Creatix\AppVersionManager\Events;

use Creatix\AppVersionManager\Models\AppVersion;
use Creatix\AppVersionManager\Support\StoreVersion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched whenever a store version sync discovers and promotes a new live build.
 */
class StoreVersionReleased
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AppVersion $appVersion,
        public StoreVersion $storeVersion,
        public int $previousBuild,
        public string $action = 'promoted', // 'created' | 'promoted'
    ) {}
}
