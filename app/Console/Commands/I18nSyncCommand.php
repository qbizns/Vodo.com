<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Translation;
use App\Services\Translation\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Sync translations from language files to database.
 * 
 * This command reads translation files and creates database
 * entries for all supported languages.
 */
class I18nSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'i18n:sync
        {--lang=* : Languages to sync (defaults to all supported)}
        {--group=* : Groups to sync (defaults to all)}
        {--module= : Module/plugin name for tracking}
        {--force : Overwrite existing translations}
        {--dry-run : Show what would be synced without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Sync translations from language files to database';

    /**
     * Execute the console command.
     */
    public function handle(TranslationService $translator): int
    {
        $languages = $this->option('lang') ?: array_keys($translator->getSupportedLanguages());
        $groups = $this->option('group') ?: null;
        $module = $this->option('module');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN - No changes will be made');
        }

        $this->info('Syncing translations...');

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        // Get source language (English) translations
        $sourceGroups = $this->getAvailableGroups('en', $groups);

        foreach ($sourceGroups as $group) {
            $this->info("Processing group: {$group}");
            
            $sourceTranslations = $this->loadGroup('en', $group);
            
            if (empty($sourceTranslations)) {
                $this->warn("  No translations found in {$group}");
                continue;
            }

            foreach ($languages as $lang) {
                if ($lang === 'en') {
                    continue; // Skip source language
                }

                $targetTranslations = $this->loadGroup($lang, $group);
                
                $this->syncGroupToDatabase(
                    $group,
                    $lang,
                    $sourceTranslations,
                    $targetTranslations,
                    $module,
                    $force,
                    $dryRun,
                    $stats
                );
            }
        }

        $this->newLine();
        $this->info('Sync completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', $stats['created']],
                ['Updated', $stats['updated']],
                ['Skipped', $stats['skipped']],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Get available translation groups.
     */
    protected function getAvailableGroups(string $lang, ?array $filter): array
    {
        $path = base_path("lang/{$lang}");
        
        if (!File::isDirectory($path)) {
            return [];
        }

        $groups = [];
        foreach (File::files($path) as $file) {
            if ($file->getExtension() === 'php') {
                $group = $file->getFilenameWithoutExtension();
                
                if ($filter && !in_array($group, $filter)) {
                    continue;
                }
                
                $groups[] = $group;
            }
        }

        return $groups;
    }

    /**
     * Load a translation group.
     */
    protected function loadGroup(string $lang, string $group): array
    {
        $path = base_path("lang/{$lang}/{$group}.php");
        
        if (!File::exists($path)) {
            return [];
        }

        return require $path;
    }

    /**
     * Sync a group to database.
     */
    protected function syncGroupToDatabase(
        string $group,
        string $lang,
        array $sourceTranslations,
        array $targetTranslations,
        ?string $module,
        bool $force,
        bool $dryRun,
        array &$stats
    ): void {
        $flattened = $this->flattenArray($sourceTranslations, $group);
        $targetFlattened = $this->flattenArray($targetTranslations, $group);

        foreach ($flattened as $key => $source) {
            $target = $targetFlattened[$key] ?? null;
            
            // Check if exists in database
            $existing = Translation::where('name', $key)
                ->where('lang', $lang)
                ->first();

            if ($existing) {
                if ($force && $target && $target !== $existing->value) {
                    if (!$dryRun) {
                        $existing->update([
                            'value' => $target,
                            'state' => 'translated',
                        ]);
                    }
                    $this->line("  [{$lang}] Updated: {$key}");
                    $stats['updated']++;
                } else {
                    $stats['skipped']++;
                }
            } else {
                if (!$dryRun) {
                    Translation::create([
                        'type' => Translation::TYPE_CODE,
                        'name' => $key,
                        'lang' => $lang,
                        'source' => $source,
                        'value' => $target,
                        'module' => $module,
                        'state' => $target ? 'translated' : 'to_translate',
                    ]);
                }
                $this->line("  [{$lang}] Created: {$key}");
                $stats['created']++;
            }
        }
    }

    /**
     * Flatten a nested array with dot notation.
     */
    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value) && !$this->isIndexedArray($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = is_array($value) ? json_encode($value) : $value;
            }
        }

        return $result;
    }

    /**
     * Check if array is indexed (for pluralization).
     */
    protected function isIndexedArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }
}
