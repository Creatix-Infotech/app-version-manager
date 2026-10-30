# Changelog

All notable changes to `creatix/app-version-manager` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-10-30

### Fixed
- Allow `firebase/php-jwt` 7.x (`^6.10|^7.0`). Apps that also use `kreait/firebase-php` 8 (which needs php-jwt 7) couldn't install the package, and every php-jwt 6.x release is flagged by a security advisory (PKSA-y2cr-5h3j-g3ys), so Composer refuses it. Only `JWT::encode()` is used, which is unchanged in 7.x.

## [1.0.0] - 2026-10-30

### Added
- Minimum app version enforcement middleware (`enforce.min.app.version`) returning HTTP 426 Upgrade Required.
- Public version checking API endpoint (`/api/v1/app-version`).
- Multi-platform policy support for iOS and Android.
- High-performance caching layer with zero-database overhead on repeated queries.
- Automated release synchronization with Apple App Store Connect API.
- Automated release synchronization with Google Play Developer API.
- Event broadcasting via `StoreVersionReleased`.
- `AppVersion` Facade and helper methods for programmatic checks.
- Comprehensive test suite covering middleware, service, models, and sync commands.
- Support for PHP 8.2, 8.3, 8.4, and 8.5.
- Support for Laravel 11.x, 12.x, and 13.x.

