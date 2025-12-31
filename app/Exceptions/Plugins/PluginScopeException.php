<?php

declare(strict_types=1);

namespace App\Exceptions\Plugins;

/**
 * Plugin Scope Exception - Thrown when a scope check fails.
 */
class PluginScopeException extends PluginException
{
    protected string $scope;
    protected ?string $pluginSlug;

    public function __construct(
        string $message,
        string $scope = '',
        ?string $pluginSlug = null,
        int $code = 403,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->scope = $scope;
        $this->pluginSlug = $pluginSlug;
    }

    /**
     * Create exception for access denied.
     */
    public static function accessDenied(string $scope, ?string $pluginSlug = null): self
    {
        $message = $pluginSlug
            ? "Plugin '{$pluginSlug}' does not have permission for scope: {$scope}"
            : "Access denied for scope: {$scope}";

        return new self($message, $scope, $pluginSlug);
    }

    /**
     * Create exception for invalid scope.
     */
    public static function invalidScope(string $scope): self
    {
        return new self("Invalid scope: {$scope}", $scope, null, 400);
    }

    /**
     * Create exception for scope requiring approval.
     */
    public static function requiresApproval(string $scope, ?string $pluginSlug = null): self
    {
        $message = "Scope '{$scope}' requires admin approval";
        return new self($message, $scope, $pluginSlug, 403);
    }

    /**
     * Get the scope that caused the exception.
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Get the plugin slug if available.
     */
    public function getPluginSlug(): ?string
    {
        return $this->pluginSlug;
    }
}
