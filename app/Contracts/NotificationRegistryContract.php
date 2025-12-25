<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for the Notification Registry.
 *
 * Manages notification types, channels, and delivery.
 */
interface NotificationRegistryContract
{
    /**
     * Register a notification type.
     *
     * @param string $name Notification type name
     * @param array $config Configuration (channels, template, etc.)
     * @param string|null $pluginSlug Owner plugin
     * @return self
     */
    public function register(string $name, array $config, ?string $pluginSlug = null): self;

    /**
     * Unregister a notification type.
     *
     * @param string $name Notification type name
     * @return bool
     */
    public function unregister(string $name): bool;

    /**
     * Get a notification type configuration.
     *
     * @param string $name Notification type name
     * @return array|null
     */
    public function get(string $name): ?array;

    /**
     * Check if a notification type exists.
     *
     * @param string $name Notification type name
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Get all notification types.
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Send a notification.
     *
     * @param string $type Notification type
     * @param mixed $notifiable User or entity to notify
     * @param array $data Notification data
     * @return void
     */
    public function send(string $type, mixed $notifiable, array $data = []): void;

    /**
     * Send notification to multiple recipients.
     *
     * @param string $type Notification type
     * @param iterable $notifiables Recipients
     * @param array $data Notification data
     * @return void
     */
    public function sendToMany(string $type, iterable $notifiables, array $data = []): void;

    /**
     * Register a notification channel.
     *
     * @param string $name Channel name
     * @param callable|string $handler Channel handler
     * @return self
     */
    public function registerChannel(string $name, callable|string $handler): self;

    /**
     * Get available channels.
     *
     * @return array<string>
     */
    public function getChannels(): array;
}
