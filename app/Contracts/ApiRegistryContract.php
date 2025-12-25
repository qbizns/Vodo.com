<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for API Registry.
 *
 * Manages API endpoints, versioning, and documentation.
 */
interface ApiRegistryContract
{
    /**
     * Register an API endpoint.
     *
     * @param string $name Endpoint name
     * @param array $config Endpoint configuration
     * @param string|null $pluginSlug Owner plugin
     * @return self
     */
    public function register(string $name, array $config, ?string $pluginSlug = null): self;

    /**
     * Get an endpoint configuration.
     *
     * @param string $name Endpoint name
     * @return array|null
     */
    public function get(string $name): ?array;

    /**
     * Get all endpoints.
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Get endpoints by version.
     *
     * @param string $version API version
     * @return Collection
     */
    public function getByVersion(string $version): Collection;

    /**
     * Register an API resource (CRUD endpoints).
     *
     * @param string $entityName Entity name
     * @param array $options Resource options
     * @param string|null $pluginSlug Owner plugin
     * @return self
     */
    public function registerResource(string $entityName, array $options = [], ?string $pluginSlug = null): self;

    /**
     * Generate OpenAPI/Swagger documentation.
     *
     * @param string|null $version Filter by version
     * @return array OpenAPI spec
     */
    public function generateOpenApiSpec(?string $version = null): array;

    /**
     * Register rate limit for an endpoint.
     *
     * @param string $name Endpoint name
     * @param array $config Rate limit config
     * @return self
     */
    public function setRateLimit(string $name, array $config): self;

    /**
     * Get rate limit for an endpoint.
     *
     * @param string $name Endpoint name
     * @return array|null
     */
    public function getRateLimit(string $name): ?array;

    /**
     * Register API middleware.
     *
     * @param string $name Middleware name
     * @param string|callable $handler Middleware handler
     * @return self
     */
    public function registerMiddleware(string $name, string|callable $handler): self;

    /**
     * Get registered middleware.
     *
     * @return Collection
     */
    public function getMiddleware(): Collection;

    /**
     * Register API transformer.
     *
     * @param string $entityName Entity name
     * @param string|callable $transformer Transformer class or callable
     * @return self
     */
    public function registerTransformer(string $entityName, string|callable $transformer): self;
}
