<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

/**
 * Extract translatable strings from the codebase.
 * 
 * Scans PHP and Blade files for translation function calls and
 * generates translation keys that need to be translated.
 */
class I18nExtractCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'i18n:extract
        {--path= : Path to scan (defaults to app and resources)}
        {--output= : Output file path (defaults to storage/app/translations.json)}
        {--format=json : Output format (json or php)}
        {--group= : Only extract for a specific group}';

    /**
     * The console command description.
     */
    protected $description = 'Extract translatable strings from PHP and Blade files';

    /**
     * Patterns to match translation calls.
     */
    protected array $patterns = [
        // __t('key') or __t('key', [...])
        '/__t\s*\(\s*[\'"]([^\'"]+)[\'"]/i',
        // __tc('key', ...) 
        '/__tc\s*\(\s*[\'"]([^\'"]+)[\'"]/i',
        // __p('plugin', 'key')
        '/__p\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]/i',
        // @t('key')
        '/@t\s*\(\s*[\'"]([^\'"]+)[\'"]/i',
        // @tc('key', ...)
        '/@tc\s*\(\s*[\'"]([^\'"]+)[\'"]/i',
        // trans('key') or __('key')
        '/(?:trans|__)\s*\(\s*[\'"]([^\'"]+)[\'"]/i',
        // trans_choice('key', ...)
        '/trans_choice\s*\(\s*[\'"]([^\'"]+)[\'"]/i',
        // Lang::get('key')
        '/Lang::get\s*\(\s*[\'"]([^\'"]+)[\'"]/i',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Extracting translatable strings...');

        $paths = $this->getPaths();
        $format = $this->option('format');
        $group = $this->option('group');
        $outputPath = $this->option('output') ?: storage_path('app/translations.json');

        $translations = [];
        $count = 0;

        foreach ($paths as $path) {
            if (!File::isDirectory($path)) {
                $this->warn("Path not found: {$path}");
                continue;
            }

            $this->info("Scanning: {$path}");
            
            $finder = new Finder();
            $finder->files()
                ->in($path)
                ->name(['*.php', '*.blade.php'])
                ->exclude(['vendor', 'node_modules', 'storage']);

            foreach ($finder as $file) {
                $content = $file->getContents();
                $extracted = $this->extractFromContent($content, $file->getRealPath());
                
                foreach ($extracted as $key => $info) {
                    if ($group && !str_starts_with($key, $group . '.')) {
                        continue;
                    }

                    if (!isset($translations[$key])) {
                        $translations[$key] = [
                            'key' => $key,
                            'files' => [],
                            'count' => 0,
                        ];
                    }
                    
                    $translations[$key]['files'][] = str_replace(base_path() . '/', '', $info['file']);
                    $translations[$key]['count']++;
                    $count++;
                }
            }
        }

        // Sort by key
        ksort($translations);

        $this->info("Found {$count} translation calls with " . count($translations) . " unique keys.");

        // Output results
        if ($format === 'php') {
            $this->outputPhp($translations, $outputPath);
        } else {
            $this->outputJson($translations, $outputPath);
        }

        // Display summary
        $this->displaySummary($translations);

        return self::SUCCESS;
    }

    /**
     * Get paths to scan.
     */
    protected function getPaths(): array
    {
        if ($path = $this->option('path')) {
            return [base_path($path)];
        }

        return [
            app_path(),
            resource_path('views'),
        ];
    }

    /**
     * Extract translation keys from content.
     */
    protected function extractFromContent(string $content, string $file): array
    {
        $translations = [];

        foreach ($this->patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $key) {
                    // Skip dynamic keys (containing variables)
                    if (str_contains($key, '$') || str_contains($key, '{')) {
                        continue;
                    }

                    $translations[$key] = [
                        'key' => $key,
                        'file' => $file,
                    ];
                }
            }
        }

        return $translations;
    }

    /**
     * Output as JSON.
     */
    protected function outputJson(array $translations, string $path): void
    {
        $output = [
            'generated_at' => now()->toIso8601String(),
            'total_keys' => count($translations),
            'keys' => array_values($translations),
        ];

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Output written to: {$path}");
    }

    /**
     * Output as PHP.
     */
    protected function outputPhp(array $translations, string $path): void
    {
        $path = str_replace('.json', '.php', $path);
        
        $grouped = [];
        foreach ($translations as $key => $info) {
            $parts = explode('.', $key, 2);
            $group = $parts[0];
            $item = $parts[1] ?? $key;

            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            
            $grouped[$group][$item] = '';
        }

        foreach ($grouped as $group => $items) {
            $groupPath = str_replace('.php', "_{$group}.php", $path);
            
            $content = "<?php\n\nreturn [\n";
            foreach ($items as $item => $value) {
                $content .= "    '{$item}' => '',\n";
            }
            $content .= "];\n";

            File::put($groupPath, $content);
            $this->info("Group '{$group}' written to: {$groupPath}");
        }
    }

    /**
     * Display summary by group.
     */
    protected function displaySummary(array $translations): void
    {
        $groups = [];
        
        foreach ($translations as $key => $info) {
            $parts = explode('.', $key, 2);
            $group = $parts[0];
            
            if (!isset($groups[$group])) {
                $groups[$group] = 0;
            }
            $groups[$group]++;
        }

        $this->newLine();
        $this->info('Summary by group:');
        
        $rows = [];
        foreach ($groups as $group => $count) {
            $rows[] = [$group, $count];
        }
        
        $this->table(['Group', 'Keys'], $rows);
    }
}
