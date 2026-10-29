<?php

namespace Creatix\AppVersionManager\Enums;

/**
 * Mobile and client platforms supported by default.
 */
enum Platform: string
{
    case ANDROID = 'android';
    case IOS = 'ios';
    case WEB = 'web';

    /**
     * Human-readable label for the platform.
     */
    public function label(): string
    {
        return match ($this) {
            self::ANDROID => 'Android',
            self::IOS => 'iOS',
            self::WEB => 'Web',
        };
    }

    /**
     * List of all enum string values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Native mobile platforms targetable by App Store / Play Store version policies.
     *
     * @return array<int, self>
     */
    public static function mobileCases(): array
    {
        return [self::ANDROID, self::IOS];
    }

    /**
     * Native mobile platform string values.
     *
     * @return array<int, string>
     */
    public static function mobileValues(): array
    {
        return array_map(static fn (self $case) => $case->value, self::mobileCases());
    }

    /**
     * Value => Label options for select dropdowns.
     *
     * @return array<string, string>
     */
    public static function mobileOptions(): array
    {
        return array_reduce(
            self::mobileCases(),
            static function (array $carry, self $case): array {
                $carry[$case->value] = $case->label();

                return $carry;
            },
            [],
        );
    }
}
