<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Contracts\NotificationRegistryContract;
use App\Models\NotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Notification Registry
 *
 * Manages notification types, channels, and delivery across the platform.
 * Supports multiple channels (database, mail, SMS, push, etc.).
 *
 * @example Register a notification type
 * ```php
 * $registry->register('invoice.overdue', [
 *     'channels' => ['database', 'mail'],
 *     'template' => 'notifications.invoice-overdue',
 *     'subject' => 'Invoice #{invoice_number} is overdue',
 *     'priority' => 'high',
 * ]);
 * ```
 *
 * @example Send a notification
 * ```php
 * $registry->send('invoice.overdue', $user, [
 *     'invoice' => $invoice,
 *     'days_overdue' => 15,
 * ]);
 * ```
 */
class NotificationRegistry implements NotificationRegistryContract
{
    /**
     * Registered notification types.
     *
     * @var array<string, array>
     */
    protected array $types = [];

    /**
     * Plugin ownership.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Custom channels.
     *
     * @var array<string, callable|string>
     */
    protected array $channels = [];

    /**
     * Default notification configuration.
     */
    protected array $defaultConfig = [
        'channels' => ['database'],
        'template' => null,
        'subject' => null,
        'priority' => 'normal',
        'queue' => true,
        'icon' => 'bell',
        'color' => 'blue',
    ];

    public function register(string $name, array $config, ?string $pluginSlug = null): self
    {
        $this->types[$name] = array_merge($this->defaultConfig, $config, [
            'name' => $name,
        ]);

        if ($pluginSlug) {
            $this->pluginOwnership[$name] = $pluginSlug;
        }

        // Persist to database for UI management
        NotificationType::updateOrCreate(
            ['slug' => $name],
            [
                'name' => $config['label'] ?? $this->generateLabel($name),
                'description' => $config['description'] ?? null,
                'channels' => $config['channels'] ?? ['database'],
                'template' => $config['template'] ?? null,
                'config' => $config,
                'plugin_slug' => $pluginSlug,
                'is_system' => $config['is_system'] ?? false,
            ]
        );

        return $this;
    }

    public function unregister(string $name): bool
    {
        if (!isset($this->types[$name])) {
            return false;
        }

        unset($this->types[$name]);
        unset($this->pluginOwnership[$name]);

        NotificationType::where('slug', $name)->delete();

        return true;
    }

    public function get(string $name): ?array
    {
        if (isset($this->types[$name])) {
            return $this->types[$name];
        }

        // Try loading from database
        $type = NotificationType::where('slug', $name)->first();
        if ($type) {
            return array_merge($this->defaultConfig, $type->config ?? [], [
                'name' => $name,
                'channels' => $type->channels,
                'template' => $type->template,
            ]);
        }

        return null;
    }

    public function has(string $name): bool
    {
        return isset($this->types[$name]) || NotificationType::where('slug', $name)->exists();
    }

    public function all(): Collection
    {
        $dbTypes = NotificationType::all()->keyBy('slug')->map(fn($type) => array_merge(
            $this->defaultConfig,
            $type->config ?? [],
            [
                'name' => $type->slug,
                'channels' => $type->channels,
                'template' => $type->template,
            ]
        ));

        return collect($this->types)->merge($dbTypes);
    }

    public function send(string $type, mixed $notifiable, array $data = []): void
    {
        $config = $this->get($type);

        if (!$config) {
            throw new \InvalidArgumentException("Notification type not found: {$type}");
        }

        $notification = $this->createNotification($type, $config, $data);

        if ($config['queue'] ?? true) {
            NotificationFacade::send($notifiable, $notification);
        } else {
            NotificationFacade::sendNow($notifiable, $notification);
        }

        // Fire hook
        do_action('notification_sent', $type, $notifiable, $data);
    }

    public function sendToMany(string $type, iterable $notifiables, array $data = []): void
    {
        $config = $this->get($type);

        if (!$config) {
            throw new \InvalidArgumentException("Notification type not found: {$type}");
        }

        $notification = $this->createNotification($type, $config, $data);

        NotificationFacade::send($notifiables, $notification);

        // Fire hook
        do_action('notification_sent_bulk', $type, $notifiables, $data);
    }

    public function registerChannel(string $name, callable|string $handler): self
    {
        $this->channels[$name] = $handler;

        return $this;
    }

    public function getChannels(): array
    {
        $defaultChannels = ['database', 'mail', 'broadcast'];

        return array_unique(array_merge($defaultChannels, array_keys($this->channels)));
    }

    /**
     * Create a notification instance.
     */
    protected function createNotification(string $type, array $config, array $data): Notification
    {
        return new DynamicNotification($type, $config, $data);
    }

    /**
     * Generate a human-readable label from notification name.
     */
    protected function generateLabel(string $name): string
    {
        return ucwords(str_replace(['.', '_', '-'], ' ', $name));
    }

    /**
     * Get notifications by category.
     *
     * @param string $category Category name
     * @return Collection
     */
    public function getByCategory(string $category): Collection
    {
        return $this->all()->filter(function ($config) use ($category) {
            return ($config['category'] ?? null) === $category;
        });
    }

    /**
     * Get user notification preferences.
     *
     * @param int $userId User ID
     * @return array
     */
    public function getUserPreferences(int $userId): array
    {
        // This would typically query a user_notification_preferences table
        return [];
    }

    /**
     * Update user notification preferences.
     *
     * @param int $userId User ID
     * @param array $preferences Preferences
     * @return void
     */
    public function setUserPreferences(int $userId, array $preferences): void
    {
        // This would typically update a user_notification_preferences table
    }

    /**
     * Get notification statistics.
     *
     * @param string|null $type Filter by type
     * @param array $dateRange Date range
     * @return array
     */
    public function getStatistics(?string $type = null, array $dateRange = []): array
    {
        // This would aggregate notification logs
        return [
            'sent' => 0,
            'read' => 0,
            'clicked' => 0,
            'by_channel' => [],
        ];
    }
}
