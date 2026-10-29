<?php

namespace Creatix\AppVersionManager\Tests\Unit;

use Creatix\AppVersionManager\Enums\Platform;
use Creatix\AppVersionManager\Enums\UpdateRequirement;
use Creatix\AppVersionManager\Facades\AppVersion as AppVersionFacade;
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

    public function test_it_bumps_dotted_version_correctly(): void
    {
        $this->assertSame('1.0.1', AppVersion::bumpVersion('1.0.0'));
        $this->assertSame('2.4.15', AppVersion::bumpVersion('2.4.14'));
        $this->assertSame('3.1', AppVersion::bumpVersion('3.0'));
    }

    public function test_it_prefills_and_increments_next_version_values(): void
    {
        $prefill = AppVersion::prefillFor('android');

        $this->assertNotNull($prefill);
        $this->assertSame('2.0.1', $prefill['latest_version']);
        $this->assertSame(21, $prefill['latest_build']);
        $this->assertSame('1.5.0', $prefill['minimum_version']);
        $this->assertSame(15, $prefill['minimum_build']);
        $this->assertSame('https://play.google.com/store/apps/details?id=com.example.app', $prefill['store_url']);
        $this->assertTrue($prefill['is_active']);

        // Test via facade as well
        $facadePrefill = AppVersionFacade::prefillFor('android');
        $this->assertSame('2.0.1', $facadePrefill['latest_version']);
        $this->assertSame(21, $facadePrefill['latest_build']);
    }
}
