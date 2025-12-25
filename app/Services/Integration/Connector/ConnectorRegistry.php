<?php

declare(strict_types=1);

namespace App\Services\Integration\Connector;

use App\Contracts\Integration\ConnectorRegistryContract;
use App\Contracts\Integration\ConnectorContract;
use App\Contracts\Integration\TriggerContract;
use App\Contracts\Integration\ActionContract;
use Illuminate\Support\Collection;

/**
 * Connector Registry
 *
 * Central registry for all integration connectors.
 * Plugins register their connectors here.
 */
class ConnectorRegistry implements ConnectorRegistryContract
{
    /**
     * Registered connectors.
     *
     * @var array<string, ConnectorContract>
     */
    protected array $connectors = [];

    /**
     * Plugin ownership.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Registered categories.
     *
     * @var array<string, array>
     */
    protected array $categories = [];

    public function __construct()
    {
        $this->registerDefaultCategories();
    }

    /**
     * Register default connector categories.
     */
    protected function registerDefaultCategories(): void
    {
        $this->categories = [
            'communication' => [
                'name' => 'Communication',
                'icon' => 'message-circle',
                'description' => 'Email, chat, SMS, and messaging services',
            ],
            'crm' => [
                'name' => 'CRM',
                'icon' => 'users',
                'description' => 'Customer relationship management',
            ],
            'social' => [
                'name' => 'Social Media',
                'icon' => 'share-2',
                'description' => 'Social media platforms',
            ],
            'payment' => [
                'name' => 'Payment',
                'icon' => 'credit-card',
                'description' => 'Payment processors and gateways',
            ],
            'ecommerce' => [
                'name' => 'E-commerce',
                'icon' => 'shopping-cart',
                'description' => 'Online stores and marketplaces',
            ],
            'productivity' => [
                'name' => 'Productivity',
                'icon' => 'briefcase',
                'description' => 'Project management and productivity tools',
            ],
            'storage' => [
                'name' => 'Storage',
                'icon' => 'hard-drive',
                'description' => 'Cloud storage and file management',
            ],
            'marketing' => [
                'name' => 'Marketing',
                'icon' => 'trending-up',
                'description' => 'Marketing automation and analytics',
            ],
            'developer' => [
                'name' => 'Developer Tools',
                'icon' => 'code',
                'description' => 'Development and DevOps tools',
            ],
            'ai' => [
                'name' => 'AI & ML',
                'icon' => 'cpu',
                'description' => 'Artificial intelligence and machine learning',
            ],
            'other' => [
                'name' => 'Other',
                'icon' => 'grid',
                'description' => 'Other integrations',
            ],
        ];
    }

    public function register(ConnectorContract $connector, ?string $pluginSlug = null): void
    {
        $name = $connector->getName();

        if (isset($this->connectors[$name])) {
            throw new \RuntimeException("Connector already registered: {$name}");
        }

        $this->connectors[$name] = $connector;

        if ($pluginSlug) {
            $this->pluginOwnership[$name] = $pluginSlug;
        }

        // Fire hook
        do_action('connector_registered', $connector, $pluginSlug);
    }

    public function unregister(string $name): bool
    {
        if (!isset($this->connectors[$name])) {
            return false;
        }

        $connector = $this->connectors[$name];

        unset($this->connectors[$name]);
        unset($this->pluginOwnership[$name]);

        // Fire hook
        do_action('connector_unregistered', $name);

        return true;
    }

    public function has(string $name): bool
    {
        return isset($this->connectors[$name]);
    }

    public function get(string $name): ?ConnectorContract
    {
        return $this->connectors[$name] ?? null;
    }

    public function all(): Collection
    {
        return collect($this->connectors);
    }

    public function getByCategory(string $category): Collection
    {
        return $this->all()->filter(
            fn(ConnectorContract $connector) => $connector->getCategory() === $category
        );
    }

    public function getByPlugin(string $pluginSlug): Collection
    {
        $connectorNames = array_keys(array_filter(
            $this->pluginOwnership,
            fn($slug) => $slug === $pluginSlug
        ));

        return $this->all()->only($connectorNames);
    }

    public function search(string $query): Collection
    {
        $query = strtolower($query);

        return $this->all()->filter(function (ConnectorContract $connector) use ($query) {
            return str_contains(strtolower($connector->getName()), $query)
                || str_contains(strtolower($connector->getDisplayName()), $query)
                || str_contains(strtolower($connector->getDescription()), $query);
        });
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function registerCategory(string $name, array $config): void
    {
        $this->categories[$name] = $config;
    }

    public function getTrigger(string $connectorName, string $triggerName): ?TriggerContract
    {
        $connector = $this->get($connectorName);

        return $connector?->getTrigger($triggerName);
    }

    public function getAction(string $connectorName, string $actionName): ?ActionContract
    {
        $connector = $this->get($connectorName);

        return $connector?->getAction($actionName);
    }

    public function getAllTriggers(): Collection
    {
        $triggers = collect();

        foreach ($this->connectors as $connector) {
            foreach ($connector->getTriggers() as $trigger) {
                $triggers->put(
                    $connector->getName() . '.' . $trigger->getName(),
                    $trigger
                );
            }
        }

        return $triggers;
    }

    public function getAllActions(): Collection
    {
        $actions = collect();

        foreach ($this->connectors as $connector) {
            foreach ($connector->getActions() as $action) {
                $actions->put(
                    $connector->getName() . '.' . $action->getName(),
                    $action
                );
            }
        }

        return $actions;
    }

    public function getCatalog(): array
    {
        $catalog = [
            'categories' => $this->categories,
            'connectors' => [],
        ];

        foreach ($this->connectors as $connector) {
            $catalog['connectors'][] = $connector->toArray();
        }

        return $catalog;
    }

    /**
     * Get connector count by category.
     */
    public function getCountByCategory(): array
    {
        $counts = [];

        foreach ($this->categories as $name => $config) {
            $counts[$name] = $this->getByCategory($name)->count();
        }

        return $counts;
    }

    /**
     * Get statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_connectors' => count($this->connectors),
            'total_triggers' => $this->getAllTriggers()->count(),
            'total_actions' => $this->getAllActions()->count(),
            'by_category' => $this->getCountByCategory(),
        ];
    }
}
