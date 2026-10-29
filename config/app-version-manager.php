<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database Table Name
    |--------------------------------------------------------------------------
    |
    | The database table used to store app version policies.
    |
    */
    'table_name' => 'app_versions',

    /*
    |--------------------------------------------------------------------------
    | App Version Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model representing an app version configuration.
    | You can extend the base model and set your custom class here.
    |
    */
    'model' => \Creatix\AppVersionManager\Models\AppVersion::class,

    /*
    |--------------------------------------------------------------------------
    | HTTP Headers for Middleware
    |--------------------------------------------------------------------------
    |
    | Mobile apps send these headers on every API request. The middleware
    | inspects them to evaluate if the client build must be force-updated.
    |
    */
    'headers' => [
        'platform' => 'X-App-Platform',
        'build' => 'X-App-Build',
    ],

    /*
    |--------------------------------------------------------------------------
    | Public Version Check API Route
    |--------------------------------------------------------------------------
    |
    | Mobile clients ping this endpoint on app launch to inspect the
    | latest available version and check whether an update is required.
    |
    */
    'api' => [
        'enabled' => true,
        'prefix' => 'api/v1',
        'middleware' => ['api'],
        'route' => 'app-version',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Caching the active version policy eliminates repetitive database lookups
    | on every single incoming API request.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'key_prefix' => 'app_version_policy_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Store Auto-Sync Credentials
    |--------------------------------------------------------------------------
    |
    | Credentials for App Store Connect and Google Play Developer APIs
    | used by `php artisan app-versions:sync`.
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Store Release Notifications
    |--------------------------------------------------------------------------
    |
    | When auto-syncer discovers and promotes a new live version, an alert
    | email can be sent to notify developers or stakeholders.
    |
    */
    'notifications' => [
        'alert_email' => env('APP_VERSION_ALERT_EMAIL', env('CRASH_ALERT_EMAIL')),
        'mail_from' => env('MAIL_FROM_ADDRESS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Fallbacks
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'update_message' => 'Please update to the latest version to continue using the app.',
    ],
];

