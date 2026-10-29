<?php

namespace Creatix\AppVersionManager\Enums;

/**
 * The update action a mobile client must take, computed by comparing its build
 * number against an active app version policy.
 */
enum UpdateRequirement: string
{
    case NONE = 'none';
    case OPTIONAL = 'optional';
    case FORCED = 'forced';

    /**
     * Whether the app is allowed to keep running (only FORCED halts execution).
     */
    public function isSupported(): bool
    {
        return $this !== self::FORCED;
    }
}

