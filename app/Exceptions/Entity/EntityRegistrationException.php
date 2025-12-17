<?php

declare(strict_types=1);

namespace App\Exceptions\Entity;

/**
 * Exception thrown when entity registration fails.
 */
class EntityRegistrationException extends EntityException
{
    /**
     * Fields that failed to register.
     */
    protected array $failedFields = [];

    /**
     * Create exception for already registered entity.
     */
    public static function alreadyExists(string $entityName, ?string $ownerPlugin = null): static
    {
        $message = "Entity '{$entityName}' is already registered";
        if ($ownerPlugin) {
            $message .= " by plugin: {$ownerPlugin}";
        }
        return static::forEntity($entityName, $message, ['owner_plugin' => $ownerPlugin]);
    }

    /**
     * Create exception for invalid entity name.
     */
    public static function invalidName(string $entityName): static
    {
        return static::forEntity(
            $entityName,
            "Invalid entity name '{$entityName}'. Must start with lowercase letter and contain only lowercase letters, numbers, and underscores."
        );
    }

    /**
     * Create exception for field registration failure.
     */
    public static function fieldRegistrationFailed(string $entityName, string $fieldSlug, string $reason): static
    {
        $exception = static::forEntity(
            $entityName,
            "Failed to register field '{$fieldSlug}': {$reason}",
            ['field_slug' => $fieldSlug]
        );
        $exception->failedFields[] = $fieldSlug;
        return $exception;
    }

    /**
     * Create exception for ownership conflict.
     */
    public static function ownershipConflict(string $entityName, string $ownerPlugin, string $requestingPlugin): static
    {
        return static::forEntity(
            $entityName,
            "Entity '{$entityName}' is owned by plugin '{$ownerPlugin}', cannot be modified by '{$requestingPlugin}'",
            [
                'owner_plugin' => $ownerPlugin,
                'requesting_plugin' => $requestingPlugin,
            ]
        );
    }

    /**
     * Create exception for system entity modification attempt.
     */
    public static function systemEntityModification(string $entityName): static
    {
        return static::forEntity(
            $entityName,
            "Cannot modify system entity '{$entityName}'"
        );
    }

    /**
     * Get failed fields.
     */
    public function getFailedFields(): array
    {
        return $this->failedFields;
    }

    /**
     * Add a failed field.
     */
    public function addFailedField(string $fieldSlug): static
    {
        $this->failedFields[] = $fieldSlug;
        return $this;
    }
}
