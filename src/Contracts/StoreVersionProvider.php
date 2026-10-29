<?php

namespace Creatix\AppVersionManager\Contracts;

use Creatix\AppVersionManager\Enums\Platform;
use Creatix\AppVersionManager\Support\StoreVersion;

/**
 * A provider that queries what version is currently live on a store.
 */
interface StoreVersionProvider
{
    public function platform(): Platform;

    /**
     * Whether this provider has the required configuration/credentials.
     */
    public function isConfigured(): bool;

    /**
     * Get the live version published on the store, or null if unavailable.
     */
    public function latestLiveVersion(): ?StoreVersion;
}
