<?php

declare(strict_types=1);

namespace Tests\Unit\PluginSDK;

use App\Services\PluginSDK\PluginGenerator;
use App\Services\PluginSDK\Templates\BasicTemplate;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Tests for PluginGenerator
 */
class PluginGeneratorTest extends TestCase
{
    protected PluginGenerator $generator;
    protected Filesystem $filesystem;
    protected string $pluginsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = Mockery::mock(Filesystem::class);
        $this->generator = new PluginGenerator($this->filesystem);

        // Mock base_path function behavior
        $this->pluginsPath = '/test/plugins';
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_available_template_types(): void
    {
        $types = $this->generator->getTemplateTypes();

        $this->assertContains('basic', $types);
        $this->assertContains('entity', $types);
        $this->assertContains('api', $types);
        $this->assertContains('marketplace', $types);
    }

    public function test_returns_template_descriptions(): void
    {
        $descriptions = $this->generator->getTemplateDescriptions();

        $this->assertIsArray($descriptions);
        $this->assertArrayHasKey('basic', $descriptions);
        $this->assertIsString($descriptions['basic']);
    }

    public function test_generate_from_template_creates_directories(): void
    {
        $template = new BasicTemplate('TestPlugin');

        // Expect directory existence check
        $this->filesystem->shouldReceive('exists')
            ->with(Mockery::pattern('/.*TestPlugin$/'))
            ->once()
            ->andReturn(false);

        // Expect directory creation for each directory in structure
        foreach ($template->getDirectoryStructure() as $dir) {
            $this->filesystem->shouldReceive('makeDirectory')
                ->with(Mockery::pattern("/.*{$dir}$/"), 0755, true, true)
                ->once();
        }

        // Expect file writes
        $this->filesystem->shouldReceive('exists')
            ->andReturn(true);
        $this->filesystem->shouldReceive('makeDirectory')
            ->andReturn(true);
        $this->filesystem->shouldReceive('put')
            ->andReturn(true);

        $result = $this->generator->generateFromTemplate($template);

        $this->assertEquals('TestPlugin', $result['name']);
        $this->assertEquals('test-plugin', $result['slug']);
        $this->assertEquals('basic', $result['template']);
    }

    public function test_generate_from_template_creates_files(): void
    {
        $template = new BasicTemplate('TestPlugin');

        $this->filesystem->shouldReceive('exists')
            ->andReturn(false);
        $this->filesystem->shouldReceive('makeDirectory')
            ->andReturn(true);

        // Track files being created
        $createdFiles = [];
        $this->filesystem->shouldReceive('put')
            ->andReturnUsing(function ($path, $content) use (&$createdFiles) {
                $createdFiles[] = $path;
                return strlen($content);
            });

        $result = $this->generator->generateFromTemplate($template);

        $this->assertNotEmpty($result['files']);
        $this->assertContains('plugin.json', $result['files']);
        $this->assertContains('composer.json', $result['files']);
    }

    public function test_generate_from_template_returns_manifest(): void
    {
        $template = new BasicTemplate('TestPlugin', [
            'version' => '2.0.0',
        ]);

        $this->filesystem->shouldReceive('exists')->andReturn(false);
        $this->filesystem->shouldReceive('makeDirectory')->andReturn(true);
        $this->filesystem->shouldReceive('put')->andReturn(true);

        $result = $this->generator->generateFromTemplate($template);

        $this->assertArrayHasKey('manifest', $result);
        $this->assertEquals('test-plugin', $result['manifest']['identifier']);
        $this->assertEquals('2.0.0', $result['manifest']['version']);
    }

    public function test_generate_throws_if_plugin_exists(): void
    {
        $template = new BasicTemplate('ExistingPlugin');

        $this->filesystem->shouldReceive('exists')
            ->with(Mockery::pattern('/.*ExistingPlugin$/'))
            ->once()
            ->andReturn(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already exists');

        $this->generator->generateFromTemplate($template);
    }

    public function test_generate_with_default_template(): void
    {
        $this->filesystem->shouldReceive('exists')->andReturn(false);
        $this->filesystem->shouldReceive('makeDirectory')->andReturn(true);
        $this->filesystem->shouldReceive('put')->andReturn(true);

        $result = $this->generator->generate('NewPlugin', []);

        $this->assertEquals('basic', $result['template']);
    }

    public function test_generate_with_specific_template(): void
    {
        $this->filesystem->shouldReceive('exists')->andReturn(false);
        $this->filesystem->shouldReceive('makeDirectory')->andReturn(true);
        $this->filesystem->shouldReceive('put')->andReturn(true);

        $result = $this->generator->generate('NewPlugin', [
            'template' => 'entity',
        ]);

        $this->assertEquals('entity', $result['template']);
    }

    public function test_generate_passes_options_through(): void
    {
        $this->filesystem->shouldReceive('exists')->andReturn(false);
        $this->filesystem->shouldReceive('makeDirectory')->andReturn(true);

        // Capture the content being written to plugin.json
        $manifestContent = null;
        $this->filesystem->shouldReceive('put')
            ->andReturnUsing(function ($path, $content) use (&$manifestContent) {
                if (str_ends_with($path, 'plugin.json')) {
                    $manifestContent = $content;
                }
                return strlen($content);
            });

        $result = $this->generator->generate('NewPlugin', [
            'template' => 'basic',
            'version' => '3.0.0',
            'description' => 'My custom plugin',
            'author' => 'Test Author',
        ]);

        $manifest = json_decode($manifestContent, true);

        $this->assertEquals('3.0.0', $manifest['version']);
        $this->assertEquals('My custom plugin', $manifest['description']);
    }
}
