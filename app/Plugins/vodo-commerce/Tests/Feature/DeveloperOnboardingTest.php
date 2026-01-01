<?php

declare(strict_types=1);

namespace VodoCommerce\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use VodoCommerce\Services\DeveloperOnboardingService;
use VodoCommerce\Services\SandboxStoreProvisioner;
use VodoCommerce\Services\PluginReviewWorkflow;

/**
 * Developer Onboarding End-to-End Tests
 *
 * Tests the complete developer journey from registration to plugin submission.
 */
class DeveloperOnboardingTest extends TestCase
{
    protected DeveloperOnboardingService $onboarding;

    protected function setUp(): void
    {
        parent::setUp();

        $sandboxProvisioner = $this->createMock(SandboxStoreProvisioner::class);
        $sandboxProvisioner->method('provision')->willReturn([
            'success' => true,
            'store' => [
                'id' => 1,
                'name' => 'Test Sandbox',
                'slug' => 'test-sandbox',
            ],
            'data_summary' => [
                'products' => 25,
                'categories' => 8,
            ],
        ]);

        $reviewWorkflow = new PluginReviewWorkflow();

        $this->onboarding = new DeveloperOnboardingService(
            $sandboxProvisioner,
            $reviewWorkflow
        );
    }

    #[Test]
    public function it_registers_a_new_developer(): void
    {
        $result = $this->onboarding->registerDeveloper([
            'email' => 'developer@example.com',
            'name' => 'John Developer',
            'password' => 'securepassword123',
            'company' => 'Dev Corp',
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('developer', $result);
        $this->assertStringStartsWith('dev_', $result['developer']['id']);
        $this->assertEquals('developer@example.com', $result['developer']['email']);
        $this->assertEquals('verify_email', $result['next_step']);
        $this->assertTrue($result['verification_email_sent']);
    }

    #[Test]
    public function it_validates_registration_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        $this->onboarding->registerDeveloper([
            'email' => 'invalid-email',
            'name' => 'John',
            'password' => 'password123',
        ]);
    }

    #[Test]
    public function it_validates_password_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters');

        $this->onboarding->registerDeveloper([
            'email' => 'test@example.com',
            'name' => 'John',
            'password' => 'short',
        ]);
    }

    #[Test]
    public function it_verifies_email_with_valid_token(): void
    {
        $developerId = 'dev_test123';
        $token = str_repeat('a', 32);

        $result = $this->onboarding->verifyEmail($developerId, $token);

        $this->assertTrue($result['success']);
        $this->assertEquals('verified', $result['status']);
        $this->assertEquals('accept_terms', $result['next_step']);
    }

    #[Test]
    public function it_rejects_invalid_verification_token(): void
    {
        $result = $this->onboarding->verifyEmail('dev_test123', 'invalid');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_accepts_developer_terms(): void
    {
        $result = $this->onboarding->acceptTerms('dev_test123', '2024.1');

        $this->assertTrue($result['success']);
        $this->assertEquals('2024.1', $result['terms_version']);
        $this->assertArrayHasKey('accepted_at', $result);
        $this->assertEquals('create_app', $result['next_step']);
    }

    #[Test]
    public function it_creates_developer_application(): void
    {
        $result = $this->onboarding->createApplication('dev_test123', [
            'name' => 'My Commerce Plugin',
            'description' => 'A payment integration plugin',
            'type' => 'plugin',
            'scopes' => ['read:orders', 'write:payments'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('application', $result);
        $this->assertStringStartsWith('app_', $result['application']['id']);
        $this->assertEquals('My Commerce Plugin', $result['application']['name']);
        $this->assertEquals('development', $result['application']['status']);

        // Verify credentials
        $this->assertArrayHasKey('credentials', $result);
        $this->assertArrayHasKey('client_id', $result['credentials']);
        $this->assertArrayHasKey('client_secret', $result['credentials']);
        $this->assertEquals(32, strlen($result['credentials']['client_id']));
        $this->assertEquals(64, strlen($result['credentials']['client_secret']));

        $this->assertEquals('provision_sandbox', $result['next_step']);
    }

    #[Test]
    public function it_provisions_sandbox_for_developer(): void
    {
        $result = $this->onboarding->provisionSandbox(
            'dev_test123',
            'developer@example.com',
            'Test App'
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('store', $result);
        $this->assertArrayHasKey('data_summary', $result);
        $this->assertEquals('scaffold_plugin', $result['next_step']);
    }

    #[Test]
    public function it_tracks_onboarding_progress(): void
    {
        $developer = [
            'id' => 'dev_test123',
            'onboarding_progress' => [
                'current_step' => 'create_app',
                'completed_steps' => ['register', 'verify_email', 'accept_terms'],
            ],
        ];

        $progress = $this->onboarding->getOnboardingProgress($developer);

        $this->assertEquals('create_app', $progress['current_step']);
        $this->assertEquals(3, $progress['completed_count']);
        $this->assertEquals(8, $progress['total_steps']);
        $this->assertEquals(3, $progress['required_completed']);
        $this->assertEquals(4, $progress['required_total']);
        $this->assertFalse($progress['can_access_dashboard']); // Not all required complete
    }

    #[Test]
    public function it_grants_dashboard_access_when_required_steps_complete(): void
    {
        $developer = [
            'id' => 'dev_test123',
            'onboarding_progress' => [
                'current_step' => 'provision_sandbox',
                'completed_steps' => ['register', 'verify_email', 'accept_terms', 'create_app'],
            ],
        ];

        $progress = $this->onboarding->getOnboardingProgress($developer);

        $this->assertTrue($progress['can_access_dashboard']);
    }

    #[Test]
    public function it_runs_complete_onboarding_simulation(): void
    {
        $result = $this->onboarding->runOnboardingSimulation([
            'email' => 'newdev@example.com',
            'name' => 'New Developer',
            'password' => 'securepassword123',
        ]);

        // Check overall success
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('steps', $result);
        $this->assertArrayHasKey('summary', $result);

        // Check each step was executed
        $expectedSteps = [
            'register',
            'verify_email',
            'accept_terms',
            'create_app',
            'provision_sandbox',
            'scaffold_plugin',
            'test_integration',
            'submit_plugin',
        ];

        foreach ($expectedSteps as $step) {
            $this->assertArrayHasKey($step, $result['steps'], "Step '{$step}' missing");
            $this->assertArrayHasKey('success', $result['steps'][$step]);
        }

        // Check summary
        $this->assertEquals(8, $result['summary']['total_steps']);
        $this->assertArrayHasKey('success_rate', $result['summary']);
        $this->assertArrayHasKey('total_time_ms', $result['summary']);
    }

    #[Test]
    public function it_provides_onboarding_checklist(): void
    {
        $checklist = $this->onboarding->getChecklist();

        $this->assertArrayHasKey('register', $checklist);
        $this->assertArrayHasKey('verify_email', $checklist);
        $this->assertArrayHasKey('accept_terms', $checklist);
        $this->assertArrayHasKey('create_app', $checklist);
        $this->assertArrayHasKey('provision_sandbox', $checklist);

        // Check step structure
        foreach ($checklist as $step) {
            $this->assertArrayHasKey('name', $step);
            $this->assertArrayHasKey('description', $step);
            $this->assertArrayHasKey('required', $step);
        }
    }

    #[Test]
    public function it_provides_documentation_links(): void
    {
        $docs = $this->onboarding->getDocumentationLinks();

        $this->assertArrayHasKey('getting_started', $docs);
        $this->assertArrayHasKey('api_reference', $docs);
        $this->assertArrayHasKey('authentication', $docs);
        $this->assertArrayHasKey('webhooks', $docs);
        $this->assertArrayHasKey('plugin_development', $docs);
        $this->assertArrayHasKey('sandbox_guide', $docs);
        $this->assertArrayHasKey('submission_guidelines', $docs);

        // Verify they're valid paths
        foreach ($docs as $link) {
            $this->assertStringStartsWith('/', $link);
        }
    }

    #[Test]
    public function it_generates_unique_developer_ids(): void
    {
        $ids = [];

        for ($i = 0; $i < 100; $i++) {
            $result = $this->onboarding->registerDeveloper([
                'email' => "dev{$i}@example.com",
                'name' => "Developer {$i}",
                'password' => 'password123',
            ]);

            $id = $result['developer']['id'];
            $this->assertNotContains($id, $ids, 'Duplicate developer ID generated');
            $ids[] = $id;
        }
    }

    #[Test]
    public function it_generates_unique_application_credentials(): void
    {
        $clientIds = [];
        $clientSecrets = [];

        for ($i = 0; $i < 50; $i++) {
            $result = $this->onboarding->createApplication("dev_{$i}", [
                'name' => "App {$i}",
            ]);

            $clientId = $result['credentials']['client_id'];
            $clientSecret = $result['credentials']['client_secret'];

            $this->assertNotContains($clientId, $clientIds);
            $this->assertNotContains($clientSecret, $clientSecrets);

            $clientIds[] = $clientId;
            $clientSecrets[] = $clientSecret;
        }
    }

    #[Test]
    public function onboarding_simulation_calculates_success_rate(): void
    {
        $result = $this->onboarding->runOnboardingSimulation([
            'email' => 'test@example.com',
            'name' => 'Test Dev',
            'password' => 'password123',
        ]);

        $summary = $result['summary'];

        $this->assertGreaterThanOrEqual(0, $summary['success_rate']);
        $this->assertLessThanOrEqual(100, $summary['success_rate']);

        $expectedRate = ($summary['successful_steps'] / $summary['total_steps']) * 100;
        $this->assertEqualsWithDelta($expectedRate, $summary['success_rate'], 0.1);
    }

    #[Test]
    public function it_measures_simulation_time(): void
    {
        $result = $this->onboarding->runOnboardingSimulation([
            'email' => 'test@example.com',
            'name' => 'Test Dev',
            'password' => 'password123',
        ]);

        $this->assertArrayHasKey('total_time_ms', $result['summary']);
        $this->assertGreaterThan(0, $result['summary']['total_time_ms']);
        $this->assertArrayHasKey('completed_at', $result['summary']);
    }

    #[Test]
    public function full_developer_journey_completes_successfully(): void
    {
        // Step 1: Register
        $registerResult = $this->onboarding->registerDeveloper([
            'email' => 'journey@example.com',
            'name' => 'Journey Test Developer',
            'password' => 'securepassword123',
            'company' => 'Journey Corp',
        ]);
        $this->assertTrue($registerResult['success']);
        $developerId = $registerResult['developer']['id'];

        // Step 2: Verify email
        $verifyResult = $this->onboarding->verifyEmail($developerId, str_repeat('x', 32));
        $this->assertTrue($verifyResult['success']);

        // Step 3: Accept terms
        $termsResult = $this->onboarding->acceptTerms($developerId, '2024.1');
        $this->assertTrue($termsResult['success']);

        // Step 4: Create application
        $appResult = $this->onboarding->createApplication($developerId, [
            'name' => 'Journey Payment Plugin',
            'description' => 'Payment integration for journey test',
            'type' => 'plugin',
        ]);
        $this->assertTrue($appResult['success']);
        $this->assertNotEmpty($appResult['credentials']['client_id']);
        $this->assertNotEmpty($appResult['credentials']['client_secret']);

        // Step 5: Provision sandbox
        $sandboxResult = $this->onboarding->provisionSandbox(
            $developerId,
            'journey@example.com',
            'Journey App'
        );
        $this->assertTrue($sandboxResult['success']);

        // Verify complete journey
        $this->assertNotNull($developerId);
        $this->assertNotNull($appResult['application']['id']);
        $this->assertNotNull($sandboxResult['store']['id']);
    }
}
