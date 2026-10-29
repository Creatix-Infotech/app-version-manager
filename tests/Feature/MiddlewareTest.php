<?php

namespace Creatix\AppVersionManager\Tests\Feature;

use Creatix\AppVersionManager\Enums\Platform;
use Creatix\AppVersionManager\Http\Middleware\EnforceMinimumAppVersion;
use Creatix\AppVersionManager\Models\AppVersion;
use Creatix\AppVersionManager\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        AppVersion::create([
            'platform' => Platform::ANDROID,
            'latest_version' => '2.0.0',
            'latest_build' => 20,
            'minimum_version' => '1.5.0',
            'minimum_build' => 15,
            'store_url' => 'https://play.google.com/store/apps/details?id=com.example.app',
            'update_message' => 'Critical update required.',
            'is_active' => true,
        ]);

        Route::middleware(EnforceMinimumAppVersion::class)->get('/test-protected', function () {
            return response()->json(['success' => true]);
        });
    }

    public function test_it_passes_through_when_app_headers_are_missing(): void
    {
        $response = $this->getJson('/test-protected');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_it_blocks_with_426_when_build_is_below_minimum(): void
    {
        $response = $this->withHeaders([
            'X-App-Platform' => 'android',
            'X-App-Build' => '10',
        ])->getJson('/test-protected');

        $response->assertStatus(426)
            ->assertJson([
                'success' => false,
                'code' => 426,
                'data' => [
                    'platform' => 'android',
                    'update_required' => 'forced',
                    'minimum_build' => 15,
                    'latest_build' => 20,
                    'store_url' => 'https://play.google.com/store/apps/details?id=com.example.app',
                ],
            ]);
    }

    public function test_it_allows_request_when_build_meets_minimum(): void
    {
        $response = $this->withHeaders([
            'X-App-Platform' => 'android',
            'X-App-Build' => '15',
        ])->getJson('/test-protected');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }
}
