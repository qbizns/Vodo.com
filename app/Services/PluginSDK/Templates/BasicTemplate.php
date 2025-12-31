<?php

declare(strict_types=1);

namespace App\Services\PluginSDK\Templates;

/**
 * Basic Plugin Template
 *
 * Minimal plugin structure for simple extensions.
 */
class BasicTemplate extends PluginTemplate
{
    public function getType(): string
    {
        return 'basic';
    }

    public function getDescription(): string
    {
        return 'Minimal plugin with basic structure - perfect for simple utilities and hooks.';
    }

    public function getDefaultScopes(): array
    {
        return [
            'hooks:subscribe',
        ];
    }

    public function getDirectoryStructure(): array
    {
        return [
            'config',
            'routes',
            'src',
            'tests',
            'Resources/views',
            'Resources/lang/en',
        ];
    }

    public function getFiles(): array
    {
        return [
            "src/{$this->name}Plugin.php" => $this->generatePluginClass(),
            "src/{$this->name}ServiceProvider.php" => $this->generateServiceProvider(),
            "config/{$this->slug}.php" => $this->generateConfig(),
            'routes/web.php' => $this->generateWebRoutes(),
            "tests/{$this->name}PluginTest.php" => $this->generateBaseTest(),
            'composer.json' => $this->generateComposerJson(),
            'plugin.json' => $this->manifest->toJson(),
            'README.md' => $this->generateReadme(),
            '.gitignore' => $this->generateGitignore(),
            'Resources/lang/en/messages.php' => $this->generateLangFile(),
        ];
    }

    protected function generateLangFile(): string
    {
        return <<<PHP
<?php

return [
    'plugin_name' => '{$this->name}',
    // Add your translations here
];
PHP;
    }
}
