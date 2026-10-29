<?php

use Creatix\AppVersionManager\Http\Controllers\AppVersionApiController;
use Illuminate\Support\Facades\Route;

$prefix = config('app-version-manager.api.prefix', 'api/v1');
$middleware = config('app-version-manager.api.middleware', ['api']);
$routeName = config('app-version-manager.api.route', 'app-version');

Route::prefix($prefix)->middleware($middleware)->group(function () use ($routeName) {
    Route::get($routeName, AppVersionApiController::class)->name('app-version.check');
});

