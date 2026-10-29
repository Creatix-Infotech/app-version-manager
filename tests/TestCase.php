<?php

namespace Creatix\AppVersionManager\Tests;

use Creatix\AppVersionManager\AppVersionManagerServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            AppVersionManagerServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'AppVersion' => \Creatix\AppVersionManager\Facades\AppVersion::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('app-version-manager.cache.enabled', false);
    }

    protected function setUpDatabase(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('platform', 32)->index();
            $table->string('latest_version', 32);
            $table->unsignedBigInteger('latest_build');
            $table->string('minimum_version', 32);
            $table->unsignedBigInteger('minimum_build');
            $table->string('store_url');
            $table->text('update_message')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['platform', 'is_active', 'latest_build'], 'app_versions_active_lookup_idx');
        });
    }
}
