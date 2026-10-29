# App Version Manager for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/creatix/app-version-manager.svg?style=flat-square)](https://packagist.org/packages/creatix/app-version-manager)
[![Total Downloads](https://img.shields.io/packagist/dt/creatix/app-version-manager.svg?style=flat-square)](https://packagist.org/packages/creatix/app-version-manager)
[![License](https://img.shields.io/packagist/l/creatix/app-version-manager.svg?style=flat-square)](https://packagist.org/packages/creatix/app-version-manager)

**App Version Manager** is an enterprise-grade Laravel package to enforce mobile app version policies (iOS & Android). It provides:

- 🛡️ **Forced update gate**: Blocks outdated apps via middleware (`426 Upgrade Required`).
- 💡 **Optional update prompt**: Informs users when a newer build is available.
- 📱 **Mobile API endpoint**: `GET /api/v1/app-version?platform=android&build=42`.
- ⚡ **High-performance caching**: Caches active policies so zero unnecessary DB queries hit your database on mobile API requests.
- 🔄 **Automated Store Sync**: Automatically queries Google Play & Apple App Store Connect to promote new releases to "latest".
- 🔔 **Release Events & Notifications**: Dispatches `StoreVersionReleased` events and optional email notifications.

---

## Requirements

- PHP `^8.2|^8.3|^8.4|^8.5`
- Laravel `^11.0|^12.0|^13.0`

---

## Installation

Install the package via Composer:

```bash
composer require creatix/app-version-manager
```

Publish the configuration file and migration:

```bash
php artisan vendor:publish --tag=app-version-manager-config
php artisan vendor:publish --tag=app-version-manager-migrations
```

Run migrations:

```bash
php artisan migrate
```

---

## Configuration

The published `config/app-version-manager.php` file allows you to customize table names, cache TTL, API routes, and store API credentials:

```php
return [
    'table_name' => 'app_versions',

    // Headers sent by the mobile app
    'headers' => [
        'platform' => 'X-App-Platform',
        'build' => 'X-App-Build',
    ],

    // Public version check endpoint
    'api' => [
        'enabled' => true,
        'prefix' => 'api/v1',
        'route' => 'app-version',
    ],

    // Cache active version policy
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
    ],

    // Store sync credentials
    'stores' => [
        'appstore' => [
            'issuer_id' => env('ASC_ISSUER_ID'),
            'key_id' => env('ASC_KEY_ID'),
            'private_key' => env('ASC_PRIVATE_KEY'),
            'app_id' => env('ASC_APP_ID'),
        ],
        'googleplay' => [
            'service_account' => env('GOOGLE_PLAY_SERVICE_ACCOUNT'),
            'package_name' => env('GOOGLE_PLAY_PACKAGE_NAME'),
        ],
    ],
];
```

---

## Usage

### 1. Protecting API Routes (Enforcing Minimum Version)

Add the `enforce.min.app.version` middleware to your mobile routes:

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'enforce.min.app.version'])->group(function () {
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::post('/jobs', [JobController::class, 'store']);
});
```

When a mobile app makes a request with:
- `X-App-Platform: android`
- `X-App-Build: 10`

If the minimum required build is `15`, the middleware immediately intercepts the request and responds with **HTTP 426 Upgrade Required**:

```json
{
    "success": false,
    "message": "A newer version of the app is required to continue.",
    "code": 426,
    "data": {
        "platform": "android",
        "update_required": "forced",
        "minimum_version": "1.5.0",
        "minimum_build": 15,
        "latest_version": "2.0.0",
        "latest_build": 20,
        "store_url": "https://play.google.com/store/apps/details?id=com.example",
        "update_message": "Please update to continue using the app."
    }
}
```

---

### 2. Checking Version on App Launch

Mobile clients can query version status on launch:

```http
GET /api/v1/app-version?platform=ios&build=18
```

Response:
```json
{
    "success": true,
    "message": "App version status retrieved successfully.",
    "data": {
        "platform": "ios",
        "update_required": "optional",
        "is_supported": true,
        "latest_version": "2.0.0",
        "latest_build": 20,
        "minimum_version": "1.5.0",
        "minimum_build": 15,
        "store_url": "https://apps.apple.com/app/id123456789",
        "update_message": "New features inside!"
    }
}
```

`update_required` will be one of:
- `forced` — Client build < minimum build.
- `optional` — Minimum build <= Client build < latest build.
- `none` — Client build >= latest build.

---

### 3. Using the Facade in PHP

```php
use Creatix\AppVersionManager\Facades\AppVersion;

// Get current active policy model
$policy = AppVersion::currentFor('android');

// Check requirement programmatically
$status = AppVersion::check('ios', 42);
```

---

### 4. Automatic Store Sync (Google Play & Apple App Store)

To sync live published builds from Google Play and App Store Connect into your database:

```bash
php artisan app-versions:sync
```

You can schedule this in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('app-versions:sync')->hourly();
```

When a new store release is promoted, the `Creatix\AppVersionManager\Events\StoreVersionReleased` event is fired.

---

## Testing

Run the test suite:

```bash
composer test
```

Or directly via PHPUnit:

```bash
vendor/bin/phpunit
```

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
