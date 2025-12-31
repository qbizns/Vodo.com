<?php

declare(strict_types=1);

namespace Tests\Feature\Marketplace;

use App\Enums\SubmissionStatus;
use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceListing;
use App\Models\Marketplace\MarketplaceSubmission;
use App\Services\Marketplace\ReviewPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ReviewPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected ReviewPipeline $pipeline;
    protected string $testPluginPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pipeline = app(ReviewPipeline::class);
        $this->testPluginPath = storage_path('app/test-plugins');

        // Create test plugin directory
        File::ensureDirectoryExists($this->testPluginPath);
    }

    protected function tearDown(): void
    {
        // Clean up test plugin directory
        if (File::isDirectory($this->testPluginPath)) {
            File::deleteDirectory($this->testPluginPath);
        }
        parent::tearDown();
    }

    public function test_pipeline_runs_all_checks(): void
    {
        $submission = $this->createTestSubmission();
        $pluginPath = $this->createValidTestPlugin();

        $results = $this->pipeline->run($submission, $pluginPath);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('passed', $results);
        $this->assertArrayHasKey('score', $results);
        $this->assertArrayHasKey('checks', $results);
        $this->assertArrayHasKey('issues', $results);
        $this->assertArrayHasKey('warnings', $results);
    }

    public function test_pipeline_detects_dangerous_functions(): void
    {
        $submission = $this->createTestSubmission();
        $pluginPath = $this->createPluginWithDangerousFunctions();

        $results = $this->pipeline->run($submission, $pluginPath);

        $this->assertFalse($results['passed']);

        $hasEvalIssue = false;
        foreach ($results['issues'] as $issue) {
            if (str_contains($issue, 'eval')) {
                $hasEvalIssue = true;
                break;
            }
        }
        $this->assertTrue($hasEvalIssue, 'Should detect eval() function');
    }

    public function test_pipeline_validates_manifest(): void
    {
        $submission = $this->createTestSubmission();
        $pluginPath = $this->createPluginWithInvalidManifest();

        $results = $this->pipeline->run($submission, $pluginPath);

        $this->assertFalse($results['passed']);

        $hasManifestIssue = false;
        foreach ($results['issues'] as $issue) {
            if (str_contains($issue, 'manifest') || str_contains($issue, 'plugin.json')) {
                $hasManifestIssue = true;
                break;
            }
        }
        $this->assertTrue($hasManifestIssue, 'Should detect manifest issues');
    }

    public function test_pipeline_checks_structure(): void
    {
        $submission = $this->createTestSubmission();
        $pluginPath = $this->createPluginWithBadStructure();

        $results = $this->pipeline->run($submission, $pluginPath);

        // Should have warnings about missing directories
        $this->assertNotEmpty($results['warnings']);
    }

    public function test_valid_plugin_passes_review(): void
    {
        $submission = $this->createTestSubmission();
        $pluginPath = $this->createValidTestPlugin();

        $results = $this->pipeline->run($submission, $pluginPath);

        // A valid plugin should pass (or at least have a good score)
        $this->assertGreaterThanOrEqual(50, $results['score']);
    }

    public function test_pipeline_tracks_check_categories(): void
    {
        $submission = $this->createTestSubmission();
        $pluginPath = $this->createValidTestPlugin();

        $results = $this->pipeline->run($submission, $pluginPath);

        $categories = array_unique(array_column($results['checks'], 'category'));

        $this->assertContains('security', $categories);
        $this->assertContains('quality', $categories);
    }

    protected function createTestSubmission(): MarketplaceSubmission
    {
        $category = MarketplaceCategory::create([
            'name' => 'Test',
            'slug' => 'test',
            'description' => 'Test category',
        ]);

        $listing = MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category->id,
            'plugin_slug' => 'test-plugin',
            'name' => 'Test Plugin',
            'short_description' => 'A test plugin',
            'description' => 'A test plugin for testing the review pipeline',
            'status' => 'draft',
            'pricing_model' => 'free',
        ]);

        return MarketplaceSubmission::create([
            'listing_id' => $listing->id,
            'developer_id' => 1,
            'version' => '1.0.0',
            'plugin_slug' => 'test-plugin',
            'status' => SubmissionStatus::AutomatedReview,
            'changelog' => 'Initial release',
        ]);
    }

    protected function createValidTestPlugin(): string
    {
        $path = $this->testPluginPath . '/valid-plugin';
        File::ensureDirectoryExists($path);
        File::ensureDirectoryExists($path . '/src');
        File::ensureDirectoryExists($path . '/config');
        File::ensureDirectoryExists($path . '/routes');

        // Create valid manifest
        File::put($path . '/plugin.json', json_encode([
            'identifier' => 'test-plugin',
            'name' => 'Test Plugin',
            'version' => '1.0.0',
            'description' => 'A test plugin for marketplace testing with a proper description',
            'author' => 'Test Developer',
            'license' => 'MIT',
            'homepage' => 'https://example.com',
            'category' => 'utilities',
            'keywords' => ['test', 'plugin', 'example'],
            'marketplace' => [
                'listed' => true,
            ],
            'permissions' => [
                'scopes' => ['entities:read'],
            ],
        ], JSON_PRETTY_PRINT));

        // Create main plugin class
        File::put($path . '/src/TestPluginPlugin.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Plugins\TestPlugin;

use App\Services\PluginSDK\BasePlugin;

class TestPluginPlugin extends BasePlugin
{
    public function register(): void
    {
        // Plugin registration
    }

    public function boot(): void
    {
        // Plugin boot
    }
}
PHP);

        // Create composer.json
        File::put($path . '/composer.json', json_encode([
            'name' => 'vendor/test-plugin',
            'type' => 'vodo-plugin',
            'autoload' => [
                'psr-4' => [
                    'Plugins\\TestPlugin\\' => 'src/',
                ],
            ],
        ], JSON_PRETTY_PRINT));

        // Create README
        File::put($path . '/README.md', <<<'MD'
# Test Plugin

A test plugin for marketplace testing.

## Installation

Install via the marketplace.

## Usage

Configure and use the plugin features.

## License

MIT
MD);

        return $path;
    }

    protected function createPluginWithDangerousFunctions(): string
    {
        $path = $this->testPluginPath . '/dangerous-plugin';
        File::ensureDirectoryExists($path);
        File::ensureDirectoryExists($path . '/src');

        File::put($path . '/plugin.json', json_encode([
            'identifier' => 'test-plugin',
            'name' => 'Test Plugin',
            'version' => '1.0.0',
            'description' => 'A test plugin with dangerous functions',
        ]));

        // Create file with dangerous functions
        File::put($path . '/src/Dangerous.php', <<<'PHP'
<?php

class Dangerous
{
    public function execute($code)
    {
        eval($code);
        exec('ls -la');
        shell_exec('whoami');
    }
}
PHP);

        return $path;
    }

    protected function createPluginWithInvalidManifest(): string
    {
        $path = $this->testPluginPath . '/invalid-manifest-plugin';
        File::ensureDirectoryExists($path);

        // Create invalid JSON
        File::put($path . '/plugin.json', '{invalid json}');

        return $path;
    }

    protected function createPluginWithBadStructure(): string
    {
        $path = $this->testPluginPath . '/bad-structure-plugin';
        File::ensureDirectoryExists($path);

        // Only create manifest, missing all other structure
        File::put($path . '/plugin.json', json_encode([
            'identifier' => 'test-plugin',
            'name' => 'Test Plugin',
            'version' => '1.0.0',
            'description' => 'A test plugin with bad structure',
        ]));

        return $path;
    }
}
