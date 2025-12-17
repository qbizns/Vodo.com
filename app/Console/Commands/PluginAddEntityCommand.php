<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PluginSDK\EntityGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Command to add an entity to a plugin.
 */
class PluginAddEntityCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'plugin:add-entity 
                            {plugin : The plugin name}
                            {entity : The entity name}
                            {--fields= : Field definitions (name:type,name:type)}
                            {--soft-deletes : Add soft deletes}
                            {--versioning : Add optimistic locking}
                            {--no-controller : Skip controller generation}';

    /**
     * The console command description.
     */
    protected $description = 'Add a new entity to a plugin';

    /**
     * Execute the console command.
     */
    public function handle(EntityGenerator $generator): int
    {
        $pluginName = Str::studly($this->argument('plugin'));
        $entityName = Str::studly($this->argument('entity'));

        // Parse fields
        $fields = $this->parseFields($this->option('fields'));

        // If no fields provided, ask interactively
        if (empty($fields)) {
            $fields = $this->askForFields();
        }

        $options = [
            'soft_deletes' => $this->option('soft-deletes'),
            'versioning' => $this->option('versioning'),
            'controller' => !$this->option('no-controller'),
        ];

        $this->info("Adding entity '{$entityName}' to plugin '{$pluginName}'");

        try {
            $result = $generator->generate($pluginName, $entityName, $fields, $options);

            $this->newLine();
            $this->info("✓ Entity created successfully!");
            $this->newLine();
            $this->line("  <comment>Table:</comment> {$result['table']}");
            $this->line("  <comment>Files created:</comment>");
            
            foreach ($result['files'] as $file) {
                $this->line("    - {$file}");
            }

            $this->newLine();
            $this->info("Registration code (add to your plugin's registerEntities method):");
            $this->newLine();
            $this->line($result['registration_code']);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Failed to create entity: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Parse fields string into array.
     */
    protected function parseFields(?string $fieldsString): array
    {
        if (empty($fieldsString)) {
            return [];
        }

        $fields = [];
        $parts = explode(',', $fieldsString);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }

            if (str_contains($part, ':')) {
                [$name, $type] = explode(':', $part, 2);
                $fields[trim($name)] = ['type' => trim($type)];
            } else {
                $fields[trim($part)] = ['type' => 'string'];
            }
        }

        return $fields;
    }

    /**
     * Ask for fields interactively.
     */
    protected function askForFields(): array
    {
        $fields = [];
        $types = ['string', 'text', 'integer', 'decimal', 'boolean', 'date', 'datetime', 'json', 'many2one'];

        $this->info("Define your entity fields (press Enter with empty name to finish):");
        $this->newLine();

        while (true) {
            $name = $this->ask('Field name (or Enter to finish)');
            
            if (empty($name)) {
                break;
            }

            $type = $this->choice('Field type', $types, 0);
            $nullable = $this->confirm('Nullable?', false);
            
            $fieldDef = [
                'type' => $type,
                'nullable' => $nullable,
            ];

            if ($type === 'many2one') {
                $relation = $this->ask('Related model class (e.g., App\\Models\\User)');
                $fieldDef['relation'] = $relation;
            }

            $fields[$name] = $fieldDef;
            $this->line("  Added: {$name} ({$type})");
        }

        return $fields;
    }
}
