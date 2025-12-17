<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ImportExport\ImportExportService;

class ExportData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'data:export 
                            {mapping : The export mapping name}
                            {--format=csv : Export format (csv, json, xlsx)}
                            {--output= : Custom output path}
                            {--limit= : Limit number of records}';

    /**
     * The console command description.
     */
    protected $description = 'Export data to a file';

    /**
     * Execute the console command.
     */
    public function handle(ImportExportService $exportService): int
    {
        $mapping = $this->argument('mapping');
        $format = $this->option('format');
        $limit = $this->option('limit');

        $this->info("Starting export: {$mapping}");
        $this->info("Format: {$format}");

        try {
            // Get the model from mapping config
            $mappings = config('import-export.mappings', []);
            if (!isset($mappings[$mapping])) {
                $this->error("Mapping not found: {$mapping}");
                return Command::FAILURE;
            }

            $modelClass = $mappings[$mapping]['model'] ?? null;
            if (!$modelClass) {
                $this->error("No model defined for mapping: {$mapping}");
                return Command::FAILURE;
            }

            // Get data
            $query = $modelClass::query();
            if ($limit) {
                $query->limit((int)$limit);
            }
            $data = $query->get();

            $this->info("Exporting {$data->count()} records...");

            // Setup progress
            $exportService->onProgress(function ($current, $total, $percent) {
                $this->output->write("\rProcessing: {$current}/{$total} ({$percent}%)");
            });

            $path = $exportService->export($mapping, $data, $format);

            $this->newLine(2);
            $this->info('✓ Export completed successfully!');
            $this->info("File saved to: {$path}");

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Export failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
