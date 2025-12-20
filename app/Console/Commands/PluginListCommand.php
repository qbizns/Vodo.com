<?php

namespace App\Console\Commands;

use App\Models\InstalledPlugin;
use Illuminate\Console\Command;

class PluginListCommand extends Command
{
    protected $signature = 'plugin:list {--status= : Filter by status}';
    protected $description = 'List installed plugins';

    public function handle(): int
    {
        $query = InstalledPlugin::with('license');

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        $plugins = $query->get();

        if ($plugins->isEmpty()) {
            $this->info('No plugins installed.');
            return 0;
        }

        $this->table(
            ['Slug', 'Name', 'Version', 'Status', 'License', 'Update'],
            $plugins->map(fn($p) => [
                $p->slug,
                $p->name,
                $p->version,
                $p->status,
                $p->getLicenseStatus(),
                $p->hasUpdate() ? '✓' : '-',
            ])
        );

        return 0;
    }
}
