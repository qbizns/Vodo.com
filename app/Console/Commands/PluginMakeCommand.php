<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PluginSDK\PluginGenerator;
use Illuminate\Console\Command;

/**
 * Command to generate a new plugin.
 */
class PluginMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'plugin:make 
                            {name : The name of the plugin}
                            {--description= : Plugin description}
                            {--author= : Plugin author}
                            {--version=1.0.0 : Initial version}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new plugin with scaffolding';

    /**
     * Execute the console command.
     */
    public function handle(PluginGenerator $generator): int
    {
        $name = $this->argument('name');
        
        $options = [
            'description' => $this->option('description') ?? "The {$name} plugin",
            'author' => $this->option('author') ?? 'Your Name',
            'version' => $this->option('version'),
        ];

        $this->info("Creating plugin: {$name}");

        try {
            $result = $generator->generate($name, $options);

            $this->newLine();
            $this->info("✓ Plugin created successfully!");
            $this->newLine();
            $this->line("  <comment>Path:</comment> {$result['path']}");
            $this->line("  <comment>Files created:</comment>");
            
            foreach ($result['files'] as $file) {
                $this->line("    - {$file}");
            }

            $this->newLine();
            $this->info("Next steps:");
            $this->line("  1. Edit the plugin class: {$result['path']}/{$result['name']}Plugin.php");
            $this->line("  2. Add entities: php artisan plugin:add-entity {$name} YourEntity");
            $this->line("  3. Run tests: php artisan plugin:test {$name}");

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Failed to create plugin: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
