<?php

namespace Creatix\AppVersionManager;

use Creatix\AppVersionManager\Commands\SyncStoreVersionsCommand;
use Creatix\AppVersionManager\Http\Middleware\EnforceMinimumAppVersion;
use Creatix\AppVersionManager\Services\AppVersionManager;
use Creatix\AppVersionManager\Services\Store\AppStoreConnectClient;
use Creatix\AppVersionManager\Services\Store\GooglePlayClient;
use Creatix\AppVersionManager\Services\StoreVersionSyncer;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class AppVersionManagerServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/app-version-manager.php',
            'app-version-manager',
        );

        $this->app->singleton(AppVersionManager::class, function () {
            return new AppVersionManager;
        });

        $this->app->singleton(AppStoreConnectClient::class);
        $this->app->singleton(GooglePlayClient::class);

        $this->app->singleton(StoreVersionSyncer::class, function ($app) {
            return new StoreVersionSyncer([
                $app->make(AppStoreConnectClient::class),
                $app->make(GooglePlayClient::class),
            ]);
        });
    }

    /**
     * Bootstrap package services.
     */
    public function boot(Router $router): void
    {
        // Register middleware alias
        $router->aliasMiddleware('enforce.min.app.version', EnforceMinimumAppVersion::class);

        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__.'/../config/app-version-manager.php' => config_path('app-version-manager.php'),
            ], 'app-version-manager-config');

            // Publish migration
            if (! class_exists('CreateAppVersionsTable')) {
                $this->publishes([
                    __DIR__.'/../database/migrations/create_app_versions_table.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_create_app_versions_table.php'),
                ], 'app-version-manager-migrations');
            }

            // Register console commands
            $this->commands([
                SyncStoreVersionsCommand::class,
            ]);
        }

        // Register API routes if enabled
        if (config('app-version-manager.api.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }
    }
}
