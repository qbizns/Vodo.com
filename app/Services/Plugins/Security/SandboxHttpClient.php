<?php

declare(strict_types=1);

namespace App\Services\Plugins\Security;

use App\Exceptions\Plugins\SandboxViolationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * Sandbox HTTP Client - Enforces network restrictions for plugin HTTP requests.
 *
 * Plugins should use this service for all outgoing HTTP requests to ensure
 * compliance with sandbox network policies.
 *
 * Features:
 * - Domain whitelist enforcement
 * - Network request rate limiting
 * - Bandwidth tracking
 * - Request/response logging
 *
 * Usage:
 *   $http = app(SandboxHttpClient::class)->forPlugin('my-plugin');
 *   $response = $http->get('https://api.example.com/data');
 */
class SandboxHttpClient
{
    protected ?string $pluginSlug = null;
    protected ?PluginSandbox $sandbox = null;

    public function __construct()
    {
        $this->sandbox = app(PluginSandbox::class);
    }

    /**
     * Create a client instance for a specific plugin.
     */
    public function forPlugin(string $pluginSlug): self
    {
        $instance = new self();
        $instance->pluginSlug = $pluginSlug;
        $instance->sandbox = $this->sandbox;

        return $instance;
    }

    /**
     * Make a GET request.
     *
     * @throws SandboxViolationException
     */
    public function get(string $url, array $query = []): Response
    {
        return $this->request('GET', $url, ['query' => $query]);
    }

    /**
     * Make a POST request.
     *
     * @throws SandboxViolationException
     */
    public function post(string $url, array $data = []): Response
    {
        return $this->request('POST', $url, ['body' => json_encode($data)]);
    }

    /**
     * Make a PUT request.
     *
     * @throws SandboxViolationException
     */
    public function put(string $url, array $data = []): Response
    {
        return $this->request('PUT', $url, ['body' => json_encode($data)]);
    }

    /**
     * Make a PATCH request.
     *
     * @throws SandboxViolationException
     */
    public function patch(string $url, array $data = []): Response
    {
        return $this->request('PATCH', $url, ['body' => json_encode($data)]);
    }

    /**
     * Make a DELETE request.
     *
     * @throws SandboxViolationException
     */
    public function delete(string $url): Response
    {
        return $this->request('DELETE', $url);
    }

    /**
     * Make a request with options.
     *
     * @throws SandboxViolationException
     */
    public function request(string $method, string $url, array $options = []): Response
    {
        $this->validateRequest($url);

        // Build the request
        $pendingRequest = Http::acceptJson()
            ->contentType('application/json')
            ->timeout(30);

        // Track request size
        $requestSize = strlen($options['body'] ?? '') + strlen($url);

        try {
            $response = match (strtoupper($method)) {
                'GET' => $pendingRequest->get($url, $options['query'] ?? []),
                'POST' => $pendingRequest->withBody($options['body'] ?? '', 'application/json')->post($url),
                'PUT' => $pendingRequest->withBody($options['body'] ?? '', 'application/json')->put($url),
                'PATCH' => $pendingRequest->withBody($options['body'] ?? '', 'application/json')->patch($url),
                'DELETE' => $pendingRequest->delete($url),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            // Track network usage
            $responseSize = strlen($response->body());
            $this->recordNetworkUsage($requestSize, $responseSize);

            return $response;

        } catch (SandboxViolationException $e) {
            throw $e;
        } catch (\Exception $e) {
            // Record error in sandbox
            if ($this->pluginSlug && $this->sandbox->isEnabled()) {
                $this->sandbox->recordError($this->pluginSlug, $e);
            }
            throw $e;
        }
    }

    /**
     * Validate the request against sandbox policies.
     *
     * @throws SandboxViolationException
     */
    protected function validateRequest(string $url): void
    {
        if (!$this->pluginSlug) {
            return; // No plugin context, skip validation
        }

        if (!$this->sandbox->isEnabled()) {
            return; // Sandbox disabled
        }

        // Extract domain from URL
        $parsed = parse_url($url);
        $domain = $parsed['host'] ?? null;

        if (!$domain) {
            throw new \InvalidArgumentException("Invalid URL: {$url}");
        }

        // Check domain whitelist
        if (!$this->sandbox->isDomainAllowed($this->pluginSlug, $domain)) {
            throw SandboxViolationException::domainNotAllowed($this->pluginSlug, $domain);
        }

        // Enforce rate limits
        $this->sandbox->enforceLimits($this->pluginSlug);

        // Increment network request counter
        $this->sandbox->incrementRateLimit($this->pluginSlug, 'network_requests');
    }

    /**
     * Record network usage for tracking.
     */
    protected function recordNetworkUsage(int $bytesOut, int $bytesIn): void
    {
        if (!$this->pluginSlug || !$this->sandbox->isEnabled()) {
            return;
        }

        $this->sandbox->recordNetworkRequest($this->pluginSlug, $bytesOut, $bytesIn);
    }

    /**
     * Create a base request with common options.
     */
    public function withOptions(array $options): PendingRequest
    {
        $request = Http::acceptJson();

        if (isset($options['timeout'])) {
            $request->timeout($options['timeout']);
        }

        if (isset($options['headers'])) {
            $request->withHeaders($options['headers']);
        }

        if (isset($options['auth'])) {
            $request->withBasicAuth($options['auth'][0], $options['auth'][1] ?? '');
        }

        if (isset($options['token'])) {
            $request->withToken($options['token']);
        }

        return $request;
    }

    /**
     * Check if a domain is allowed without making a request.
     */
    public function isDomainAllowed(string $domain): bool
    {
        if (!$this->pluginSlug || !$this->sandbox->isEnabled()) {
            return true;
        }

        return $this->sandbox->isDomainAllowed($this->pluginSlug, $domain);
    }

    /**
     * Get the whitelist of allowed domains for the current plugin.
     */
    public function getAllowedDomains(): array
    {
        if (!$this->pluginSlug) {
            return [];
        }

        return $this->sandbox->getNetworkWhitelist($this->pluginSlug);
    }
}
