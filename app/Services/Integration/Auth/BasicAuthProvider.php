<?php

declare(strict_types=1);

namespace App\Services\Integration\Auth;

use App\Contracts\Integration\AuthProviderContract;

/**
 * Basic Authentication Provider
 *
 * Handles HTTP Basic Authentication (username/password).
 */
class BasicAuthProvider implements AuthProviderContract
{
    public function getType(): string
    {
        return 'basic';
    }

    public function getFields(): array
    {
        return [
            'username' => [
                'type' => 'text',
                'label' => 'Username',
                'required' => true,
            ],
            'password' => [
                'type' => 'password',
                'label' => 'Password',
                'required' => true,
            ],
        ];
    }

    public function validate(array $credentials): array
    {
        $errors = [];

        if (empty($credentials['username'])) {
            $errors['username'] = 'Username is required';
        }

        if (empty($credentials['password'])) {
            $errors['password'] = 'Password is required';
        }

        return $errors;
    }

    public function buildAuth(array $credentials, array $config = []): array
    {
        $encoded = base64_encode(
            ($credentials['username'] ?? '') . ':' . ($credentials['password'] ?? '')
        );

        return [
            'headers' => [
                'Authorization' => 'Basic ' . $encoded,
            ],
            'query' => [],
        ];
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
        throw new \BadMethodCallException('Basic auth does not support code exchange');
    }

    public function refreshToken(array $config, string $refreshToken): array
    {
        throw new \BadMethodCallException('Basic auth does not support token refresh');
    }

    public function revokeTokens(array $config, array $tokens): bool
    {
        return false;
    }
}
