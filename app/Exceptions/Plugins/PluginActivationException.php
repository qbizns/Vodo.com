<?php

declare(strict_types=1);

namespace App\Exceptions\Plugins;

/**
 * Exception thrown when plugin activation fails.
 */
class PluginActivationException extends PluginException
{
    /**
     * The activation phase that failed.
     */
    protected string $failedPhase = 'unknown';

    /**
     * Create a new activation exception.
     */
    public static function registrationFailed(string $slug, string $reason): static
    {
        $exception = static::forPlugin($slug, "Plugin registration failed: {$reason}");
        $exception->failedPhase = 'registration';
        return $exception;
    }

    /**
     * Create exception for migration failure.
     */
    public static function migrationFailed(string $slug, string $migration, string $reason): static
    {
        $exception = static::forPlugin(
            $slug,
            "Plugin migration failed: {$migration} - {$reason}",
            ['migration' => $migration]
        );
        $exception->failedPhase = 'migration';
        return $exception;
    }

    /**
     * Create exception for boot failure.
     */
    public static function bootFailed(string $slug, string $reason): static
    {
        $exception = static::forPlugin($slug, "Plugin boot failed: {$reason}");
        $exception->failedPhase = 'boot';
        return $exception;
    }

    /**
     * Get the phase that failed.
     */
    public function getFailedPhase(): string
    {
        return $this->failedPhase;
    }
}
