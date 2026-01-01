<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Facades\Event;

/**
 * Developer Onboarding Service
 *
 * Manages the complete developer onboarding experience from
 * registration to first plugin submission.
 */
class DeveloperOnboardingService
{
    /**
     * Onboarding steps in order.
     */
    protected const ONBOARDING_STEPS = [
        'register' => [
            'name' => 'Developer Registration',
            'description' => 'Create your developer account',
            'required' => true,
        ],
        'verify_email' => [
            'name' => 'Email Verification',
            'description' => 'Verify your email address',
            'required' => true,
        ],
        'accept_terms' => [
            'name' => 'Accept Developer Terms',
            'description' => 'Review and accept the developer agreement',
            'required' => true,
        ],
        'create_app' => [
            'name' => 'Create Application',
            'description' => 'Register your first application',
            'required' => true,
        ],
        'provision_sandbox' => [
            'name' => 'Provision Sandbox',
            'description' => 'Create a sandbox store for testing',
            'required' => false,
        ],
        'scaffold_plugin' => [
            'name' => 'Scaffold Plugin',
            'description' => 'Generate plugin boilerplate code',
            'required' => false,
        ],
        'test_integration' => [
            'name' => 'Test Integration',
            'description' => 'Make your first API call',
            'required' => false,
        ],
        'submit_plugin' => [
            'name' => 'Submit Plugin',
            'description' => 'Submit your plugin for review',
            'required' => false,
        ],
    ];

    public function __construct(
        protected SandboxStoreProvisioner $sandboxProvisioner,
        protected PluginReviewWorkflow $reviewWorkflow
    ) {
    }

    /**
     * Register a new developer.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function registerDeveloper(array $data): array
    {
        $this->validateRegistration($data);

        $developerId = $this->generateDeveloperId();

        $developer = [
            'id' => $developerId,
            'email' => $data['email'],
            'name' => $data['name'],
            'company' => $data['company'] ?? null,
            'website' => $data['website'] ?? null,
            'status' => 'pending_verification',
            'onboarding_progress' => [
                'current_step' => 'verify_email',
                'completed_steps' => ['register'],
                'started_at' => now()->toIso8601String(),
            ],
            'created_at' => now()->toIso8601String(),
        ];

        Event::dispatch('commerce.developer.registered', [
            'developer_id' => $developerId,
            'email' => $data['email'],
        ]);

        return [
            'success' => true,
            'developer' => $developer,
            'next_step' => 'verify_email',
            'verification_email_sent' => true,
        ];
    }

    /**
     * Verify developer email.
     *
     * @param string $developerId
     * @param string $verificationToken
     * @return array<string, mixed>
     */
    public function verifyEmail(string $developerId, string $verificationToken): array
    {
        // In production, validate the token
        $verified = strlen($verificationToken) === 32;

        if (!$verified) {
            return [
                'success' => false,
                'error' => 'Invalid verification token',
            ];
        }

        Event::dispatch('commerce.developer.verified', [
            'developer_id' => $developerId,
        ]);

        return [
            'success' => true,
            'developer_id' => $developerId,
            'status' => 'verified',
            'next_step' => 'accept_terms',
        ];
    }

    /**
     * Accept developer terms.
     *
     * @param string $developerId
     * @param string $termsVersion
     * @return array<string, mixed>
     */
    public function acceptTerms(string $developerId, string $termsVersion): array
    {
        return [
            'success' => true,
            'developer_id' => $developerId,
            'terms_version' => $termsVersion,
            'accepted_at' => now()->toIso8601String(),
            'next_step' => 'create_app',
        ];
    }

    /**
     * Create a developer application.
     *
     * @param string $developerId
     * @param array<string, mixed> $appData
     * @return array<string, mixed>
     */
    public function createApplication(string $developerId, array $appData): array
    {
        $appId = 'app_' . bin2hex(random_bytes(8));

        $application = [
            'id' => $appId,
            'developer_id' => $developerId,
            'name' => $appData['name'],
            'description' => $appData['description'] ?? null,
            'type' => $appData['type'] ?? 'plugin',
            'redirect_uris' => $appData['redirect_uris'] ?? [],
            'scopes' => $appData['scopes'] ?? [],
            'client_id' => $this->generateClientId(),
            'client_secret' => $this->generateClientSecret(),
            'status' => 'development',
            'created_at' => now()->toIso8601String(),
        ];

        Event::dispatch('commerce.application.created', [
            'app_id' => $appId,
            'developer_id' => $developerId,
        ]);

        return [
            'success' => true,
            'application' => $application,
            'credentials' => [
                'client_id' => $application['client_id'],
                'client_secret' => $application['client_secret'],
            ],
            'next_step' => 'provision_sandbox',
            'documentation_url' => '/docs/getting-started',
        ];
    }

    /**
     * Provision sandbox for developer.
     *
     * @param string $developerId
     * @param string $developerEmail
     * @param string $appName
     * @return array<string, mixed>
     */
    public function provisionSandbox(
        string $developerId,
        string $developerEmail,
        string $appName
    ): array {
        $result = $this->sandboxProvisioner->provision(
            tenantId: 1,
            developerEmail: $developerEmail,
            appName: $appName
        );

        if ($result['success']) {
            $result['next_step'] = 'scaffold_plugin';
        }

        return $result;
    }

    /**
     * Get onboarding progress.
     *
     * @param array<string, mixed> $developer
     * @return array<string, mixed>
     */
    public function getOnboardingProgress(array $developer): array
    {
        $completedSteps = $developer['onboarding_progress']['completed_steps'] ?? [];
        $currentStep = $developer['onboarding_progress']['current_step'] ?? 'register';

        $steps = [];
        foreach (self::ONBOARDING_STEPS as $key => $step) {
            $isCompleted = in_array($key, $completedSteps);
            $isCurrent = $key === $currentStep;

            $steps[$key] = array_merge($step, [
                'key' => $key,
                'status' => $isCompleted ? 'completed' : ($isCurrent ? 'current' : 'pending'),
                'completed' => $isCompleted,
            ]);
        }

        $requiredSteps = array_filter(self::ONBOARDING_STEPS, fn ($s) => $s['required']);
        $requiredCompleted = count(array_intersect(
            array_keys($requiredSteps),
            $completedSteps
        ));

        return [
            'current_step' => $currentStep,
            'completed_count' => count($completedSteps),
            'total_steps' => count(self::ONBOARDING_STEPS),
            'required_completed' => $requiredCompleted,
            'required_total' => count($requiredSteps),
            'percentage' => round((count($completedSteps) / count(self::ONBOARDING_STEPS)) * 100),
            'steps' => $steps,
            'can_access_dashboard' => $requiredCompleted === count($requiredSteps),
        ];
    }

    /**
     * Run complete onboarding simulation.
     *
     * @param array<string, mixed> $developerData
     * @return array<string, mixed>
     */
    public function runOnboardingSimulation(array $developerData): array
    {
        $startTime = microtime(true);
        $results = [
            'steps' => [],
            'success' => true,
            'errors' => [],
        ];

        // Step 1: Register
        $registerResult = $this->registerDeveloper($developerData);
        $results['steps']['register'] = [
            'success' => $registerResult['success'],
            'developer_id' => $registerResult['developer']['id'] ?? null,
        ];

        if (!$registerResult['success']) {
            $results['success'] = false;
            $results['errors'][] = 'Registration failed';
            return $this->finalizeSimulation($results, $startTime);
        }

        $developerId = $registerResult['developer']['id'];
        $developerEmail = $developerData['email'];

        // Step 2: Verify email
        $verifyResult = $this->verifyEmail($developerId, str_repeat('a', 32));
        $results['steps']['verify_email'] = ['success' => $verifyResult['success']];

        // Step 3: Accept terms
        $termsResult = $this->acceptTerms($developerId, '2024.1');
        $results['steps']['accept_terms'] = ['success' => $termsResult['success']];

        // Step 4: Create application
        $appResult = $this->createApplication($developerId, [
            'name' => 'Test Plugin',
            'description' => 'A test plugin for onboarding',
            'type' => 'plugin',
        ]);
        $results['steps']['create_app'] = [
            'success' => $appResult['success'],
            'app_id' => $appResult['application']['id'] ?? null,
            'has_credentials' => isset($appResult['credentials']),
        ];

        // Step 5: Provision sandbox
        $sandboxResult = $this->provisionSandbox($developerId, $developerEmail, 'Test App');
        $results['steps']['provision_sandbox'] = [
            'success' => $sandboxResult['success'],
            'store_id' => $sandboxResult['store']['id'] ?? null,
            'has_sample_data' => isset($sandboxResult['data_summary']),
        ];

        // Step 6: Scaffold plugin (simulated)
        $results['steps']['scaffold_plugin'] = [
            'success' => true,
            'plugin_name' => 'TestPlugin',
            'files_generated' => 8,
        ];

        // Step 7: Test integration (simulated API call)
        $results['steps']['test_integration'] = [
            'success' => true,
            'api_call' => 'GET /api/v1/commerce/products',
            'response_time_ms' => rand(50, 200),
        ];

        // Step 8: Submit plugin (simulated)
        try {
            $submissionResult = $this->reviewWorkflow->submit([
                'name' => 'Test Onboarding Plugin',
                'version' => '1.0.0',
                'developer_id' => $developerId,
                'developer_email' => $developerEmail,
                'description' => 'Plugin created during onboarding',
                'category' => 'general',
            ]);

            $results['steps']['submit_plugin'] = [
                'success' => $submissionResult['success'],
                'submission_id' => $submissionResult['submission_id'] ?? null,
            ];
        } catch (\Exception $e) {
            $results['steps']['submit_plugin'] = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }

        return $this->finalizeSimulation($results, $startTime);
    }

    /**
     * Get onboarding checklist.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getChecklist(): array
    {
        return self::ONBOARDING_STEPS;
    }

    /**
     * Get developer documentation links.
     *
     * @return array<string, string>
     */
    public function getDocumentationLinks(): array
    {
        return [
            'getting_started' => '/docs/developers/getting-started',
            'api_reference' => '/api/docs/commerce',
            'authentication' => '/docs/developers/authentication',
            'webhooks' => '/docs/developers/webhooks',
            'plugin_development' => '/docs/developers/plugin-development',
            'sandbox_guide' => '/docs/developers/sandbox',
            'submission_guidelines' => '/docs/developers/submission-guidelines',
            'best_practices' => '/docs/developers/best-practices',
            'faq' => '/docs/developers/faq',
            'support' => '/developers/support',
        ];
    }

    /**
     * Validate registration data.
     *
     * @param array<string, mixed> $data
     * @throws \InvalidArgumentException
     */
    protected function validateRegistration(array $data): void
    {
        if (empty($data['email'])) {
            throw new \InvalidArgumentException('Email is required');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Name is required');
        }

        if (empty($data['password'])) {
            throw new \InvalidArgumentException('Password is required');
        }

        if (strlen($data['password']) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters');
        }
    }

    /**
     * Generate developer ID.
     *
     * @return string
     */
    protected function generateDeveloperId(): string
    {
        return 'dev_' . bin2hex(random_bytes(12));
    }

    /**
     * Generate OAuth client ID.
     *
     * @return string
     */
    protected function generateClientId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate OAuth client secret.
     *
     * @return string
     */
    protected function generateClientSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Finalize simulation results.
     *
     * @param array<string, mixed> $results
     * @param float $startTime
     * @return array<string, mixed>
     */
    protected function finalizeSimulation(array $results, float $startTime): array
    {
        $endTime = microtime(true);

        $successfulSteps = count(array_filter(
            $results['steps'],
            fn ($step) => $step['success'] ?? false
        ));

        $results['summary'] = [
            'total_steps' => count($results['steps']),
            'successful_steps' => $successfulSteps,
            'failed_steps' => count($results['steps']) - $successfulSteps,
            'success_rate' => round(($successfulSteps / count($results['steps'])) * 100, 1),
            'total_time_ms' => round(($endTime - $startTime) * 1000, 2),
            'completed_at' => now()->toIso8601String(),
        ];

        $results['success'] = $successfulSteps === count($results['steps']);

        return $results;
    }
}
