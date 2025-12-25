<?php

declare(strict_types=1);

namespace App\Services\Integration\Auth;

use App\Contracts\Integration\AuthProviderContract;

/**
 * API Key Authentication Provider
 *
 * Handles API key based authentication with flexible placement.
 */
class ApiKeyProvider implements AuthProviderContract
{
    public function getType(): string
    {
        return 'api_key';
    }

    public function getFields(): array
    {
        return [
            'api_key' => [
                'type' => 'password',
                'label' => 'API Key',
                'required' => true,
                'help' => 'Your API key for authentication',
            ],
        ];
    }

    public function validate(array $credentials): array
    {
        $errors = [];

        if (empty($credentials['api_key'])) {
            $errors['api_key'] = 'API key is required';
        }

        return $errors;
    }

    public function buildAuth(array $credentials, array $config = []): array
    {
        $placement = $config['placement'] ?? 'header';
        $paramName = $config['param_name'] ?? 'Authorization';
        $prefix = $config['prefix'] ?? 'Bearer ';

        return match ($placement) {
            'header' => [
                'headers' => [
                    $paramName => $prefix . $credentials['api_key'],
                ],
                'query' => [],
            ],
            'query' => [
                'headers' => [],
                'query' => [
                    $paramName => $credentials['api_key'],
                ],
            ],
            'body' => [
                'headers' => [],
                'query' => [],
                'body' => [
                    $paramName => $credentials['api_key'],
                ],
            ],
            default => [
                'headers' => [
                    'Authorization' => 'Bearer ' . $credentials['api_key'],
                ],
                'query' => [],
            ],
        };
    }

    public function requiresRedirect(): bool
    {
        return false;
    }

    public function getAuthorizationUrl(array $config, string $state, string $redirectUri): ?string
    {
        return null;
    }

    public function exchangeCode(array $config, string $code, string $redirectUri): array
    {
        throw new \BadMethodCallException('API Key auth does not support code exchange');
    }

    public function refreshToken(array $config, string $refreshToken): array
    {
        throw new \BadMethodCallException('API Key auth does not support token refresh');
    }

    public function revokeTokens(array $config, array $tokens): bool
    {
        return false;
    }
}
