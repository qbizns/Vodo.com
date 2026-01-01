<?php

declare(strict_types=1);

namespace VodoCommerce\Console\Commands;

use Illuminate\Console\Command;
use VodoCommerce\Services\SandboxStoreProvisioner;

/**
 * Provision Sandbox Store Command
 *
 * CLI command to provision sandbox stores for plugin developers.
 */
class ProvisionSandboxCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commerce:sandbox:provision
                            {email : Developer email address}
                            {--name= : Application name}
                            {--store= : Store name}
                            {--currency=USD : Store currency}
                            {--expiry=30 : Expiry in days}
                            {--tenant=1 : Tenant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision a new sandbox store for plugin development';

    public function __construct(
        protected SandboxStoreProvisioner $provisioner
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $appName = $this->option('name') ?? 'Developer App';
        $storeName = $this->option('store');
        $currency = $this->option('currency');
        $expiry = (int) $this->option('expiry');
        $tenantId = (int) $this->option('tenant');

        $this->info('Provisioning sandbox store...');
        $this->newLine();

        $result = $this->provisioner->provision(
            tenantId: $tenantId,
            developerEmail: $email,
            appName: $appName,
            options: [
                'store_name' => $storeName,
                'currency' => $currency,
                'expiry_days' => $expiry,
            ]
        );

        if (!$result['success']) {
            $this->error('Failed to provision sandbox store: ' . ($result['error'] ?? 'Unknown error'));
            return self::FAILURE;
        }

        $this->info('Sandbox store provisioned successfully!');
        $this->newLine();

        // Store details
        $this->table(['Property', 'Value'], [
            ['Store ID', $result['store']['id']],
            ['Store Name', $result['store']['name']],
            ['Store Slug', $result['store']['slug']],
            ['Domain', $result['store']['domain']],
            ['Expires At', $result['store']['expires_at']],
        ]);

        $this->newLine();
        $this->info('Sample Data:');
        $this->table(['Type', 'Count'], [
            ['Products', $result['data_summary']['products']],
            ['Categories', $result['data_summary']['categories']],
            ['Customers', $result['data_summary']['customers']],
            ['Orders', $result['data_summary']['orders']],
        ]);

        $this->newLine();
        $this->warn('API Credentials (save these - secret will not be shown again):');
        $this->newLine();

        $this->line('OAuth Credentials:');
        $this->line('  Client ID:     ' . $result['credentials']['oauth']['client_id']);
        $this->line('  Client Secret: ' . $result['credentials']['oauth']['client_secret']);
        $this->line('  Auth URL:      ' . $result['credentials']['oauth']['authorization_url']);
        $this->line('  Token URL:     ' . $result['credentials']['oauth']['token_url']);

        $this->newLine();
        $this->line('API Key: ' . $result['credentials']['api_key']);

        $this->newLine();
        $this->line('API Base URL:     ' . $result['api_base_url']);
        $this->line('Documentation:    ' . $result['documentation_url']);

        $this->newLine();
        $this->line('Provisioning time: ' . $result['provisioning_time_ms'] . 'ms');

        return self::SUCCESS;
    }
}
