<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Translation;
use App\Services\Translation\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Report missing translations.
 * 
 * This command shows all translation keys that are missing
 * for one or more languages.
 */
class I18nMissingCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'i18n:missing
        {--lang=* : Languages to check (defaults to all supported)}
        {--group=* : Groups to check (defaults to all)}
        {--source=files : Source to check (files or database)}
        {--output= : Output file path (optional)}
        {--format=table : Output format (table, json, csv)}';

    /**
     * The console command description.
     */
    protected $description = 'Report missing translations for languages';

    /**
     * Execute the console command.
     */
    public function handle(TranslationService $translator): int
    {
        $supportedLanguages = $translator->getSupportedLanguages();
        $languages = $this->option('lang') ?: array_keys($supportedLanguages);
        $groups = $this->option('group') ?: null;
        $source = $this->option('source');
        $format = $this->option('format');
        $outputPath = $this->option('output');

        $this->info('Checking for missing translations...');

        // Filter to exclude source language (English)
        $languages = array_filter($languages, fn($lang) => $lang !== 'en');

        $missing = [];

        if ($source === 'database') {
            $missing = $this->checkDatabase($languages, $groups);
        } else {
            $missing = $this->checkFiles($languages, $groups);
        }

        // Output results
        if (empty($missing)) {
            $this->info('No missing translations found!');
            return self::SUCCESS;
        }

        switch ($format) {
            case 'json':
                $this->outputJson($missing, $outputPath);
                break;
            case 'csv':
                $this->outputCsv($missing, $outputPath);
                break;
            default:
                $this->outputTable($missing, $supportedLanguages);
        }

        // Summary
        $this->newLine();
        $this->info('Summary:');
        
        $summary = [];
        foreach ($missing as $item) {
            if (!isset($summary[$item['lang']])) {
                $summary[$item['lang']] = 0;
            }
            $summary[$item['lang']]++;
        }

        $rows = [];
        foreach ($summary as $lang => $count) {
            $langInfo = $supportedLanguages[$lang] ?? [];
            $rows[] = [
                $lang,
                $langInfo['name'] ?? $lang,
                $count,
            ];
        }

        $this->table(['Code', 'Language', 'Missing'], $rows);

        return self::FAILURE; // Return failure to indicate missing translations
    }

    /**
     * Check files for missing translations.
     */
    protected function checkFiles(array $languages, ?array $groups): array
    {
        $missing = [];
        $sourceGroups = $this->getAvailableGroups('en', $groups);

        foreach ($sourceGroups as $group) {
            $sourceTranslations = $this->loadGroup('en', $group);
            $flattened = $this->flattenArray($sourceTranslations, $group);

            foreach ($languages as $lang) {
                $targetTranslations = $this->loadGroup($lang, $group);
                $targetFlattened = $this->flattenArray($targetTranslations, $group);

                foreach ($flattened as $key => $source) {
                    if (!isset($targetFlattened[$key]) || empty($targetFlattened[$key])) {
                        $missing[] = [
                            'key' => $key,
                            'lang' => $lang,
                            'group' => $group,
                            'source' => $source,
                        ];
                    }
                }
            }
        }

        return $missing;
    }

    /**
     * Check database for missing translations.
     */
    protected function checkDatabase(array $languages, ?array $groups): array
    {
        $missing = [];

        $query = Translation::query()
            ->whereIn('lang', $languages)
            ->where(function ($q) {
                $q->whereNull('value')
                    ->orWhere('value', '')
                    ->orWhere('state', 'to_translate');
            });

        if ($groups) {
            $query->where(function ($q) use ($groups) {
                foreach ($groups as $group) {
                    $q->orWhere('name', 'like', "{$group}.%");
                }
            });
        }

        foreach ($query->get() as $translation) {
            $parts = explode('.', $translation->name, 2);
            $missing[] = [
                'key' => $translation->name,
                'lang' => $translation->lang,
                'group' => $parts[0],
                'source' => $translation->source,
            ];
        }

        return $missing;
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
     * Check if array is indexed.
     */
    protected function isIndexedArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Output as table.
     */
    protected function outputTable(array $missing, array $supportedLanguages): void
    {
        // Group by language
        $grouped = [];
        foreach ($missing as $item) {
            $grouped[$item['lang']][] = $item;
        }

        foreach ($grouped as $lang => $items) {
            $langInfo = $supportedLanguages[$lang] ?? [];
            $langName = $langInfo['name'] ?? $lang;
            $this->newLine();
            $this->warn("Missing translations for {$lang} ({$langName}):");

            $rows = [];
            foreach ($items as $item) {
                $rows[] = [
                    $item['group'],
                    $item['key'],
                    substr($item['source'], 0, 50) . (strlen($item['source']) > 50 ? '...' : ''),
                ];
            }

            $this->table(['Group', 'Key', 'Source (EN)'], $rows);
        }
    }

    /**
     * Output as JSON.
     */
    protected function outputJson(array $missing, ?string $outputPath): void
    {
        $output = json_encode([
            'generated_at' => now()->toIso8601String(),
            'total_missing' => count($missing),
            'items' => $missing,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($outputPath) {
            File::put($outputPath, $output);
            $this->info("Output written to: {$outputPath}");
        } else {
            $this->line($output);
        }
    }

    /**
     * Output as CSV.
     */
    protected function outputCsv(array $missing, ?string $outputPath): void
    {
        $lines = ['lang,group,key,source'];
        
        foreach ($missing as $item) {
            $source = str_replace('"', '""', $item['source']);
            $lines[] = "{$item['lang']},{$item['group']},{$item['key']},\"{$source}\"";
        }

        $output = implode("\n", $lines);

        if ($outputPath) {
            File::put($outputPath, $output);
            $this->info("Output written to: {$outputPath}");
        } else {
            $this->line($output);
        }
    }
}
