<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ImportExport\ImportExportService;

class ImportData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'data:import 
                            {mapping : The import mapping name}
                            {file : Path to the import file}
                            {--format= : File format (csv, json, xlsx)}
                            {--validate-only : Only validate without importing}
                            {--allow-partial : Continue even with some errors}';

    /**
     * The console command description.
     */
    protected $description = 'Import data from a file';

    /**
     * Execute the console command.
     */
    public function handle(ImportExportService $importService): int
    {
        $mapping = $this->argument('mapping');
        $file = $this->argument('file');
        $validateOnly = $this->option('validate-only');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return Command::FAILURE;
        }

        $this->info("Starting import: {$mapping}");
        $this->info("File: {$file}");

        // Setup progress bar
        $importService->onProgress(function ($current, $total, $percent) {
            $this->output->write("\rProcessing: {$current}/{$total} ({$percent}%)");
        });

        try {
            if ($validateOnly) {
                $this->info("\nValidating data...");
                $result = $importService->validate($mapping, $file);
            } else {
                $this->info("\nImporting data...");
                $result = $importService->import($mapping, $file, [
                    'format' => $this->option('format'),
                    'allow_partial' => $this->option('allow-partial'),
                ]);
            }

            $this->newLine(2);

            if ($result->success) {
                $this->info('✓ Import completed successfully!');
            } else {
                $this->error('✗ Import completed with errors.');
            }

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Rows', $result->totalRows],
                    ['Created', $result->created],
                    ['Updated', $result->updated],
                    ['Skipped', $result->skipped],
                    ['Failed', $result->failed],
                    ['Duration', round($result->duration, 2) . 's'],
                ]
            );

            if (!empty($result->errors)) {
                $this->newLine();
                $this->warn('Errors:');
                foreach (array_slice($result->errors, 0, 10) as $row => $errors) {
                    $this->line("  Row {$row}: " . implode(', ', $errors));
                }
                if (count($result->errors) > 10) {
                    $this->line('  ... and ' . (count($result->errors) - 10) . ' more errors');
                }
            }

            return $result->success ? Command::SUCCESS : Command::FAILURE;

        } catch (\Throwable $e) {
            $this->error("Import failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
