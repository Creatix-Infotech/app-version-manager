<?php

namespace Creatix\AppVersionManager\Support;

/**
 * The version a store currently serves to users: display version string and integer build number.
 */
readonly class StoreVersion
{
    public function __construct(
        public string $version,
        public int $build,
    ) {}
}
