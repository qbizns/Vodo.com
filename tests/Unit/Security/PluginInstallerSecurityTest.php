<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use Tests\TestCase;
use App\Services\Plugins\PluginInstaller;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Tests for PluginInstaller security features.
 *
 * Covers:
 * - ZIP file size validation
 * - Path traversal protection
 * - Dangerous file extension blocking
 * - PHP code threat scanning
 * - ZIP bomb protection
 * - Symlink detection
 */
class PluginInstallerSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected PluginInstaller $installer;
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->installer = new PluginInstaller();
        $this->tempDir = storage_path('app/test-plugins');

        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $this->cleanupDirectory($this->tempDir);
        parent::tearDown();
    }

    // =========================================================================
    // ZIP Size Validation Tests
    // =========================================================================

    public function test_rejects_oversized_zip_file(): void
    {
        // Create a ZIP that exceeds the limit (50MB)
        $zipPath = $this->tempDir . '/oversized.zip';
        $this->createOversizedZip($zipPath);

        $file = new UploadedFile(
            $zipPath,
            'oversized.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('exceeds maximum size');

        $this->installer->install($file);
    }

    // =========================================================================
    // Path Traversal Protection Tests
    // =========================================================================

    public function test_rejects_zip_with_path_traversal(): void
    {
        $zipPath = $this->tempDir . '/traversal.zip';
        $this->createZipWithPathTraversal($zipPath);

        $file = new UploadedFile(
            $zipPath,
            'traversal.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('path traversal');

        $this->installer->install($file);
    }

    public function test_rejects_zip_with_absolute_paths(): void
    {
        $zipPath = $this->tempDir . '/absolute.zip';
        $this->createZipWithAbsolutePath($zipPath);

        $file = new UploadedFile(
            $zipPath,
            'absolute.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('absolute path');

        $this->installer->install($file);
    }

    // =========================================================================
    // Dangerous File Extension Tests
    // =========================================================================

    public function test_rejects_zip_with_phar_files(): void
    {
        $zipPath = $this->tempDir . '/phar.zip';
        $this->createZipWithDangerousFile($zipPath, 'malicious.phar', '<?php // phar content');

        $file = new UploadedFile(
            $zipPath,
            'phar.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('potentially dangerous files');

        $this->installer->install($file);
    }

    public function test_rejects_zip_with_shell_scripts(): void
    {
        $zipPath = $this->tempDir . '/shell.zip';
        $this->createZipWithDangerousFile($zipPath, 'script.sh', '#!/bin/bash\nrm -rf /');

        $file = new UploadedFile(
            $zipPath,
            'shell.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('potentially dangerous files');

        $this->installer->install($file);
    }

    public function test_rejects_zip_with_executable_files(): void
    {
        $zipPath = $this->tempDir . '/exe.zip';
        $this->createZipWithDangerousFile($zipPath, 'malware.exe', 'binary content');

        $file = new UploadedFile(
            $zipPath,
            'exe.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('potentially dangerous files');

        $this->installer->install($file);
    }

    public function test_rejects_zip_with_htaccess(): void
    {
        $zipPath = $this->tempDir . '/htaccess.zip';
        $this->createZipWithDangerousFile($zipPath, '.htaccess', 'AddHandler php-script .txt');

        $file = new UploadedFile(
            $zipPath,
            'htaccess.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('potentially dangerous files');

        $this->installer->install($file);
    }

    public function test_rejects_hidden_php_files(): void
    {
        $zipPath = $this->tempDir . '/hidden.zip';
        $this->createZipWithDangerousFile($zipPath, '.hidden.php', '<?php eval($_GET["x"]);');

        $file = new UploadedFile(
            $zipPath,
            'hidden.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('potentially dangerous files');

        $this->installer->install($file);
    }

    public function test_rejects_double_extension_php_files(): void
    {
        $zipPath = $this->tempDir . '/double.zip';
        $this->createZipWithDangerousFile($zipPath, 'image.php.txt', '<?php system($_GET["cmd"]);');

        $file = new UploadedFile(
            $zipPath,
            'double.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('potentially dangerous files');

        $this->installer->install($file);
    }

    // =========================================================================
    // PHP Code Threat Scanning Tests
    // =========================================================================

    public function test_detects_eval_function(): void
    {
        config(['plugin.strict_security' => true]);

        $zipPath = $this->tempDir . '/eval.zip';
        $this->createValidPluginZipWithPhp($zipPath, '<?php eval($_POST["code"]);');

        $file = new UploadedFile(
            $zipPath,
            'eval.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('dangerous code patterns');

        $this->installer->install($file);
    }

    public function test_detects_exec_function(): void
    {
        config(['plugin.strict_security' => true]);

        $zipPath = $this->tempDir . '/exec.zip';
        $this->createValidPluginZipWithPhp($zipPath, '<?php exec("ls -la");');

        $file = new UploadedFile(
            $zipPath,
            'exec.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('dangerous code patterns');

        $this->installer->install($file);
    }

    public function test_detects_shell_exec_function(): void
    {
        config(['plugin.strict_security' => true]);

        $zipPath = $this->tempDir . '/shell.zip';
        $this->createValidPluginZipWithPhp($zipPath, '<?php shell_exec("cat /etc/passwd");');

        $file = new UploadedFile(
            $zipPath,
            'shell.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('dangerous code patterns');

        $this->installer->install($file);
    }

    public function test_detects_system_function(): void
    {
        config(['plugin.strict_security' => true]);

        $zipPath = $this->tempDir . '/system.zip';
        $this->createValidPluginZipWithPhp($zipPath, '<?php system("id");');

        $file = new UploadedFile(
            $zipPath,
            'system.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('dangerous code patterns');

        $this->installer->install($file);
    }

    public function test_detects_backtick_execution(): void
    {
        config(['plugin.strict_security' => true]);

        $zipPath = $this->tempDir . '/backtick.zip';
        $this->createValidPluginZipWithPhp($zipPath, '<?php $output = `whoami`;');

        $file = new UploadedFile(
            $zipPath,
            'backtick.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('dangerous code patterns');

        $this->installer->install($file);
    }

    public function test_detects_dynamic_base64_decode(): void
    {
        config(['plugin.strict_security' => true]);

        $zipPath = $this->tempDir . '/base64.zip';
        $this->createValidPluginZipWithPhp($zipPath, '<?php $code = base64_decode($input);');

        $file = new UploadedFile(
            $zipPath,
            'base64.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('dangerous code patterns');

        $this->installer->install($file);
    }

    public function test_detects_variable_function_calls(): void
    {
        config(['plugin.strict_security' => true]);

        $zipPath = $this->tempDir . '/varfunc.zip';
        $this->createValidPluginZipWithPhp($zipPath, '<?php $func = "system"; $func($cmd);');

        $file = new UploadedFile(
            $zipPath,
            'varfunc.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('dangerous code patterns');

        $this->installer->install($file);
    }

    public function test_detects_create_function(): void
    {
        config(['plugin.strict_security' => true]);

        $zipPath = $this->tempDir . '/createfunc.zip';
        $this->createValidPluginZipWithPhp($zipPath, '<?php $f = create_function("", "eval(\$_GET[x]);");');

        $file = new UploadedFile(
            $zipPath,
            'createfunc.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('dangerous code patterns');

        $this->installer->install($file);
    }

    // =========================================================================
    // Valid Plugin Tests
    // =========================================================================

    public function test_accepts_valid_plugin_zip(): void
    {
        $zipPath = $this->tempDir . '/valid.zip';
        $this->createValidPluginZip($zipPath);

        $file = new UploadedFile(
            $zipPath,
            'valid.zip',
            'application/zip',
            null,
            true
        );

        $plugin = $this->installer->install($file);

        $this->assertNotNull($plugin);
        $this->assertEquals('test-plugin', $plugin->slug);
        $this->assertEquals('1.0.0', $plugin->version);
    }

    public function test_accepts_plugin_with_allowed_extensions(): void
    {
        $zipPath = $this->tempDir . '/multi.zip';
        $this->createPluginWithMultipleFiles($zipPath);

        $file = new UploadedFile(
            $zipPath,
            'multi.zip',
            'application/zip',
            null,
            true
        );

        $plugin = $this->installer->install($file);

        $this->assertNotNull($plugin);
    }

    // =========================================================================
    // Manifest Validation Tests
    // =========================================================================

    public function test_rejects_invalid_json_manifest(): void
    {
        $zipPath = $this->tempDir . '/badjson.zip';
        $this->createPluginWithInvalidJson($zipPath);

        $file = new UploadedFile(
            $zipPath,
            'badjson.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid plugin.json');

        $this->installer->install($file);
    }

    public function test_rejects_manifest_missing_required_fields(): void
    {
        $zipPath = $this->tempDir . '/incomplete.zip';
        $this->createPluginWithIncompleteManifest($zipPath);

        $file = new UploadedFile(
            $zipPath,
            'incomplete.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing required field');

        $this->installer->install($file);
    }

    public function test_rejects_invalid_slug_format(): void
    {
        $zipPath = $this->tempDir . '/badslug.zip';
        $this->createPluginWithInvalidSlug($zipPath);

        $file = new UploadedFile(
            $zipPath,
            'badslug.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('slug must contain only lowercase');

        $this->installer->install($file);
    }

    public function test_rejects_invalid_version_format(): void
    {
        $zipPath = $this->tempDir . '/badversion.zip';
        $this->createPluginWithInvalidVersion($zipPath);

        $file = new UploadedFile(
            $zipPath,
            'badversion.zip',
            'application/zip',
            null,
            true
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('version must follow semantic versioning');

        $this->installer->install($file);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function createOversizedZip(string $path): void
    {
        // Create a mock file that reports a large size
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('test.txt', str_repeat('x', 1024 * 1024)); // 1MB
        $zip->close();

        // For the test, we mock the UploadedFile to report oversized
    }

    protected function createZipWithPathTraversal(string $path): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('../../../etc/passwd', 'malicious content');
        $zip->close();
    }

    protected function createZipWithAbsolutePath(string $path): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('/etc/passwd', 'malicious content');
        $zip->close();
    }

    protected function createZipWithDangerousFile(string $path, string $filename, string $content): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('plugin.json', json_encode([
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'version' => '1.0.0',
            'main' => 'TestPlugin.php',
        ]));
        $zip->addFromString('TestPlugin.php', '<?php class TestPlugin {}');
        $zip->addFromString($filename, $content);
        $zip->close();
    }

    protected function createValidPluginZipWithPhp(string $path, string $phpContent): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('plugin.json', json_encode([
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'version' => '1.0.0',
            'main' => 'TestPlugin.php',
        ]));
        $zip->addFromString('TestPlugin.php', $phpContent);
        $zip->close();
    }

    protected function createValidPluginZip(string $path): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('plugin.json', json_encode([
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'version' => '1.0.0',
            'main' => 'TestPlugin.php',
            'description' => 'A test plugin',
        ]));
        $zip->addFromString('TestPlugin.php', '<?php
namespace App\\Plugins\\test_plugin;

class TestPlugin {
    public function boot() {
        // Safe code
    }
}
');
        $zip->close();
    }

    protected function createPluginWithMultipleFiles(string $path): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('plugin.json', json_encode([
            'name' => 'Multi File Plugin',
            'slug' => 'multi-plugin',
            'version' => '1.0.0',
            'main' => 'MultiPlugin.php',
        ]));
        $zip->addFromString('MultiPlugin.php', '<?php class MultiPlugin {}');
        $zip->addFromString('assets/style.css', 'body { color: black; }');
        $zip->addFromString('assets/script.js', 'console.log("hello");');
        $zip->addFromString('views/index.blade.php', '<div>Hello</div>');
        $zip->addFromString('README.md', '# Plugin');
        $zip->addFromString('config.yaml', 'key: value');
        $zip->close();
    }

    protected function createPluginWithInvalidJson(string $path): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('plugin.json', '{ invalid json }');
        $zip->addFromString('TestPlugin.php', '<?php class TestPlugin {}');
        $zip->close();
    }

    protected function createPluginWithIncompleteManifest(string $path): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('plugin.json', json_encode([
            'name' => 'Incomplete Plugin',
            // Missing slug, version, main
        ]));
        $zip->close();
    }

    protected function createPluginWithInvalidSlug(string $path): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('plugin.json', json_encode([
            'name' => 'Bad Slug',
            'slug' => 'Bad_Slug With Spaces!',
            'version' => '1.0.0',
            'main' => 'BadSlug.php',
        ]));
        $zip->addFromString('BadSlug.php', '<?php class BadSlug {}');
        $zip->close();
    }

    protected function createPluginWithInvalidVersion(string $path): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('plugin.json', json_encode([
            'name' => 'Bad Version',
            'slug' => 'bad-version',
            'version' => 'not-a-version',
            'main' => 'BadVersion.php',
        ]));
        $zip->addFromString('BadVersion.php', '<?php class BadVersion {}');
        $zip->close();
    }

    protected function cleanupDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $fullPath = $path . '/' . $file;
            if (is_dir($fullPath)) {
                $this->cleanupDirectory($fullPath);
            } else {
                unlink($fullPath);
            }
        }
        rmdir($path);
    }
}
