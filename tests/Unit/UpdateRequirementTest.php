<?php

namespace Creatix\AppVersionManager\Tests\Unit;

use Creatix\AppVersionManager\Enums\Platform;
use Creatix\AppVersionManager\Enums\UpdateRequirement;
use Creatix\AppVersionManager\Models\AppVersion;
use Creatix\AppVersionManager\Tests\TestCase;

class UpdateRequirementTest extends TestCase
{
    private AppVersion $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = AppVersion::create([
            'platform' => Platform::ANDROID,
            'latest_version' => '2.0.0',
            'latest_build' => 20,
            'minimum_version' => '1.5.0',
            'minimum_build' => 15,
            'store_url' => 'https://play.google.com/store/apps/details?id=com.example.app',
            'update_message' => 'Please update to continue.',
            'is_active' => true,
        ]);
    }

    public function test_it_forces_update_when_build_is_below_minimum(): void
    {
        $requirement = $this->policy->requirementFor(10);

        $this->assertSame(UpdateRequirement::FORCED, $requirement);
        $this->assertFalse($requirement->isSupported());
    }

    public function test_it_prompts_optional_update_when_between_minimum_and_latest(): void
    {
        $requirement = $this->policy->requirementFor(17);

        $this->assertSame(UpdateRequirement::OPTIONAL, $requirement);
        $this->assertTrue($requirement->isSupported());
    }

    public function test_it_requires_no_update_when_build_is_equal_or_higher_than_latest(): void
    {
        $exactRequirement = $this->policy->requirementFor(20);
        $higherRequirement = $this->policy->requirementFor(25);

        $this->assertSame(UpdateRequirement::NONE, $exactRequirement);
        $this->assertTrue($exactRequirement->isSupported());

        $this->assertSame(UpdateRequirement::NONE, $higherRequirement);
        $this->assertTrue($higherRequirement->isSupported());
    }
}
