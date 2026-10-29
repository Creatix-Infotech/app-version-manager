<?php

namespace Creatix\AppVersionManager\Tests\Feature;

use Creatix\AppVersionManager\Enums\Platform;
use Creatix\AppVersionManager\Models\AppVersion;
use Creatix\AppVersionManager\Tests\TestCase;

class ApiEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        AppVersion::create([
            'platform' => Platform::IOS,
            'latest_version' => '3.0.0',
            'latest_build' => 300,
            'minimum_version' => '2.5.0',
            'minimum_build' => 250,
            'store_url' => 'https://apps.apple.com/app/id123456789',
            'update_message' => 'New features inside!',
            'is_active' => true,
        ]);
    }

    public function test_it_returns_validation_error_when_parameters_are_missing(): void
    {
        $response = $this->getJson('/api/v1/app-version');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['platform', 'build']);
    }

    public function test_it_returns_optional_update_status(): void
    {
        $response = $this->getJson('/api/v1/app-version?platform=ios&build=270');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'platform' => 'ios',
                    'update_required' => 'optional',
                    'is_supported' => true,
                    'latest_version' => '3.0.0',
                    'latest_build' => 300,
                    'minimum_build' => 250,
                ],
            ]);
    }

    public function test_it_returns_forced_update_status(): void
    {
        $response = $this->getJson('/api/v1/app-version?platform=ios&build=200');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'platform' => 'ios',
                    'update_required' => 'forced',
                    'is_supported' => false,
                    'minimum_build' => 250,
                    'latest_build' => 300,
                ],
            ]);
    }
}
