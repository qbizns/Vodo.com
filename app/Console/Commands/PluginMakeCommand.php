<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PluginSDK\PluginGenerator;
use App\Services\PluginSDK\Templates\TemplateFactory;
use Illuminate\Console\Command;

/**
 * Command to generate a new plugin with template support.
 */
class PluginMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'plugin:make
                            {name : The name of the plugin}
                            {--template=basic : Template type (basic, entity, api, marketplace)}
                            {--description= : Plugin description}
                            {--author= : Plugin author}
                            {--version=1.0.0 : Initial version}
                            {--entity= : Entity name for entity template}
                            {--interactive : Interactive mode to select options}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new plugin with scaffolding using templates';

    /**
     * Execute the console command.
     */
    public function handle(PluginGenerator $generator): int
    {
        $name = $this->argument('name');

        // Show template options if interactive or invalid template
        $template = $this->option('template');
        if ($this->option('interactive') || !TemplateFactory::exists($template)) {
            $template = $this->selectTemplate();
        }

        $options = $this->gatherOptions($name, $template);

        $this->info("Creating plugin: {$name}");
        $this->line("  Template: <comment>{$template}</comment>");
        $this->newLine();

        try {
            $result = $generator->generate($name, $options);

            $this->displaySuccess($result);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Failed to create plugin: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Select template interactively.
     */
    protected function selectTemplate(): string
    {
        $descriptions = TemplateFactory::getDescriptions();

        $this->info('Available templates:');
        $this->newLine();

        foreach ($descriptions as $type => $description) {
            $this->line("  <comment>{$type}</comment>");
            $this->line("    {$description}");
            $this->newLine();
        }

        return $this->choice(
            'Select a template',
            array_keys($descriptions),
            'basic'
        );
    }

    /**
     * Gather all options for plugin generation.
     */
    protected function gatherOptions(string $name, string $template): array
    {
        $options = [
            'template' => $template,
            'description' => $this->option('description') ?? "The {$name} plugin",
            'author' => $this->option('author') ?? 'Developer',
            'version' => $this->option('version'),
        ];

        // Entity-specific options
        if ($template === 'entity' && $this->option('entity')) {
            $options['entity_name'] = $this->option('entity');
        }

        // Interactive mode - ask for more details
        if ($this->option('interactive')) {
            $options = $this->gatherInteractiveOptions($options, $template);
        }

        return $options;
    }

    /**
     * Gather options interactively.
     */
    protected function gatherInteractiveOptions(array $options, string $template): array
    {
        if (!$this->option('description')) {
            $options['description'] = $this->ask(
                'Plugin description',
                $options['description']
            );
        }

        if (!$this->option('author')) {
            $options['author'] = $this->ask('Author name', $options['author']);
        }

        // Template-specific prompts
        if ($template === 'entity' && !isset($options['entity_name'])) {
            $options['entity_name'] = $this->ask(
                'Primary entity name (e.g., Product, Order)',
                null
            );
        }

        if ($template === 'marketplace') {
            $options['category'] = $this->choice(
                'Marketplace category',
                ['utilities', 'integrations', 'analytics', 'communications', 'payments', 'shipping'],
                'utilities'
            );

            $options['pricing'] = $this->choice(
                'Pricing model',
                ['free', 'one-time', 'subscription'],
                'free'
            );
        }

        return $options;
    }

    /**
     * Display success message with details.
     */
    protected function displaySuccess(array $result): void
    {
        $this->newLine();
        $this->info("✓ Plugin created successfully!");
        $this->newLine();

        $this->line("  <comment>Name:</comment>     {$result['name']}");
        $this->line("  <comment>Slug:</comment>     {$result['slug']}");
        $this->line("  <comment>Template:</comment> {$result['template']}");
        $this->line("  <comment>Path:</comment>     {$result['path']}");

        $this->newLine();
        $this->line("  <comment>Files created:</comment> " . count($result['files']));

        // Show first 10 files
        $files = array_slice($result['files'], 0, 10);
        foreach ($files as $file) {
            $this->line("    - {$file}");
        }

        if (count($result['files']) > 10) {
            $remaining = count($result['files']) - 10;
            $this->line("    <fg=gray>... and {$remaining} more files</>");
        }

        $this->newLine();
        $this->displayNextSteps($result);
    }

    /**
     * Display next steps based on template.
     */
    protected function displayNextSteps(array $result): void
    {
        $this->info("Next steps:");

        $name = $result['name'];
        $slug = $result['slug'];
        $template = $result['template'];

        $steps = [
            "1. Review plugin.json manifest: {$result['path']}/plugin.json",
            "2. Activate the plugin: php artisan plugin:activate {$slug}",
        ];

        switch ($template) {
            case 'entity':
                $steps[] = "3. Run migrations: php artisan migrate";
                $steps[] = "4. Test the plugin: php artisan plugin:test {$slug}";
                break;

            case 'api':
                $steps[] = "3. Configure API settings in config/{$slug}.php";
                $steps[] = "4. Test API endpoints: php artisan plugin:test {$slug}";
                break;

            case 'marketplace':
                $steps[] = "3. Configure OAuth in config/{$slug}.php";
                $steps[] = "4. Add marketplace assets (icon, screenshots)";
                $steps[] = "5. Validate for marketplace: php artisan plugin:validate {$slug}";
                break;

            default:
                $steps[] = "3. Implement your plugin logic";
                $steps[] = "4. Test the plugin: php artisan plugin:test {$slug}";
        }

        foreach ($steps as $step) {
            $this->line("  {$step}");
        }

        $this->newLine();
        $this->line("  <fg=gray>Documentation: https://docs.vodo.com/plugins</>");
    }
}
