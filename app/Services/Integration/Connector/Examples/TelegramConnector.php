<?php

declare(strict_types=1);

namespace App\Services\Integration\Connector\Examples;

use App\Services\Integration\Connector\AbstractConnector;

/**
 * Telegram Connector Example
 *
 * This is an example of how plugins would implement a connector.
 * In production, this would be in a plugin directory like:
 * plugins/telegram/src/TelegramConnector.php
 *
 * @example Plugin registration
 * ```php
 * // In plugin's boot() method:
 * public function registerConnectors(ConnectorRegistry $registry): void
 * {
 *     $registry->register(new TelegramConnector());
 * }
 * ```
 */
class TelegramConnector extends AbstractConnector
{
    public function getName(): string
    {
        return 'telegram';
    }

    public function getDisplayName(): string
    {
        return 'Telegram';
    }

    public function getDescription(): string
    {
        return 'Send messages, photos, and documents to Telegram chats and channels';
    }

    public function getIcon(): string
    {
        return 'connectors/telegram.svg';
    }

    public function getColor(): string
    {
        return '#0088cc';
    }

    public function getCategory(): string
    {
        return 'communication';
    }

    public function getAuthType(): string
    {
        return 'api_key';
    }

    public function getAuthConfig(): array
    {
        return [
            'fields' => [
                'bot_token' => [
                    'type' => 'password',
                    'label' => 'Bot Token',
                    'required' => true,
                    'help' => 'Your Telegram Bot token from @BotFather',
                    'placeholder' => '123456789:ABCdefGHIjklMNOpqrsTUVwxyz',
                ],
            ],
        ];
    }

    public function getBaseUrl(): string
    {
        return 'https://api.telegram.org';
    }

    protected function getTestEndpoint(): string
    {
        return '/getMe';
    }

    protected function getAuthHeaders(array $credentials): array
    {
        return []; // Telegram uses URL-based auth
    }

    protected function makeRequest(
        string $method,
        string $endpoint,
        array $credentials,
        array $data = [],
        array $headers = []
    ): array {
        $botToken = $credentials['bot_token'] ?? $credentials['api_key'] ?? '';
        $url = $this->getBaseUrl() . "/bot{$botToken}" . $endpoint;

        $response = \Illuminate\Support\Facades\Http::timeout(30)
            ->withHeaders(array_merge($this->getDefaultHeaders($credentials), $headers));

        $response = match (strtoupper($method)) {
            'GET' => $response->get($url, $data),
            'POST' => $response->post($url, $data),
            default => throw new \InvalidArgumentException("Invalid method: {$method}"),
        };

        if ($response->failed()) {
            throw new \App\Exceptions\Integration\ApiRequestException(
                $response->json('description') ?? 'Telegram API error',
                $response->status()
            );
        }

        return $response->json('result') ?? [];
    }

    protected function parseUserFromTestResponse(array $response): ?array
    {
        return [
            'id' => $response['id'] ?? null,
            'name' => $response['first_name'] ?? 'Bot',
            'username' => $response['username'] ?? null,
        ];
    }

    protected function registerTriggers(): void
    {
        $this->addTrigger(new TelegramNewMessageTrigger($this));
    }

    protected function registerActions(): void
    {
        $this->addAction(new TelegramSendMessageAction($this));
        $this->addAction(new TelegramSendPhotoAction($this));
    }

    public function getRateLimits(): array
    {
        return [
            'requests' => 30,
            'per_seconds' => 1,
        ];
    }
}

/**
 * Telegram New Message Trigger
 */
class TelegramNewMessageTrigger extends \App\Services\Integration\Connector\AbstractTrigger
{
    public function getName(): string
    {
        return 'new_message';
    }

    public function getDisplayName(): string
    {
        return 'New Message';
    }

    public function getDescription(): string
    {
        return 'Triggers when a new message is received';
    }

    public function getType(): string
    {
        return 'webhook';
    }

    public function getOutputFields(): array
    {
        return [
            'message_id' => ['type' => 'integer', 'label' => 'Message ID'],
            'text' => ['type' => 'string', 'label' => 'Message Text'],
            'from' => ['type' => 'object', 'label' => 'Sender'],
            'chat' => ['type' => 'object', 'label' => 'Chat'],
            'date' => ['type' => 'integer', 'label' => 'Timestamp'],
        ];
    }

    public function registerWebhook(array $credentials, string $webhookUrl, array $config): array
    {
        $botToken = $credentials['bot_token'] ?? '';

        $response = \Illuminate\Support\Facades\Http::post(
            "https://api.telegram.org/bot{$botToken}/setWebhook",
            ['url' => $webhookUrl]
        );

        if (!$response->json('ok')) {
            throw new \App\Exceptions\Integration\IntegrationException(
                $response->json('description') ?? 'Failed to set webhook'
            );
        }

        return ['webhook_id' => 'telegram_webhook'];
    }

    public function unregisterWebhook(array $credentials, string $webhookId): bool
    {
        $botToken = $credentials['bot_token'] ?? '';

        $response = \Illuminate\Support\Facades\Http::post(
            "https://api.telegram.org/bot{$botToken}/deleteWebhook"
        );

        return $response->json('ok', false);
    }

    public function processWebhook(array $payload, array $headers, array $config): ?array
    {
        $message = $payload['message'] ?? null;

        if (!$message) {
            return null;
        }

        return [
            'message_id' => $message['message_id'],
            'text' => $message['text'] ?? '',
            'from' => $message['from'] ?? [],
            'chat' => $message['chat'] ?? [],
            'date' => $message['date'],
        ];
    }

    public function getSampleOutput(): array
    {
        return [
            'message_id' => 123,
            'text' => 'Hello, bot!',
            'from' => [
                'id' => 12345678,
                'first_name' => 'John',
                'username' => 'johndoe',
            ],
            'chat' => [
                'id' => -100123456789,
                'type' => 'group',
                'title' => 'My Group',
            ],
            'date' => 1703500800,
        ];
    }
}

/**
 * Telegram Send Message Action
 */
class TelegramSendMessageAction extends \App\Services\Integration\Connector\AbstractAction
{
    public function getName(): string
    {
        return 'send_message';
    }

    public function getDisplayName(): string
    {
        return 'Send Message';
    }

    public function getDescription(): string
    {
        return 'Send a text message to a chat';
    }

    public function getGroup(): string
    {
        return 'messages';
    }

    public function getInputFields(): array
    {
        return [
            'chat_id' => [
                'type' => 'string',
                'label' => 'Chat ID',
                'required' => true,
                'help' => 'The chat ID or @username to send the message to',
            ],
            'text' => [
                'type' => 'text',
                'label' => 'Message',
                'required' => true,
                'help' => 'The message text to send',
            ],
            'parse_mode' => [
                'type' => 'select',
                'label' => 'Parse Mode',
                'options' => [
                    '' => 'None',
                    'Markdown' => 'Markdown',
                    'MarkdownV2' => 'Markdown V2',
                    'HTML' => 'HTML',
                ],
                'default' => '',
            ],
            'disable_notification' => [
                'type' => 'boolean',
                'label' => 'Silent',
                'default' => false,
            ],
        ];
    }

    public function getOutputFields(): array
    {
        return [
            'message_id' => ['type' => 'integer', 'label' => 'Message ID'],
            'chat' => ['type' => 'object', 'label' => 'Chat'],
            'date' => ['type' => 'integer', 'label' => 'Timestamp'],
        ];
    }

    public function execute(array $credentials, array $input): array
    {
        $botToken = $credentials['bot_token'] ?? '';

        $params = [
            'chat_id' => $input['chat_id'],
            'text' => $input['text'],
        ];

        if (!empty($input['parse_mode'])) {
            $params['parse_mode'] = $input['parse_mode'];
        }

        if (!empty($input['disable_notification'])) {
            $params['disable_notification'] = true;
        }

        $response = \Illuminate\Support\Facades\Http::post(
            "https://api.telegram.org/bot{$botToken}/sendMessage",
            $params
        );

        if (!$response->json('ok')) {
            throw new \App\Exceptions\Integration\ApiRequestException(
                $response->json('description') ?? 'Failed to send message',
                $response->status()
            );
        }

        $result = $response->json('result');

        return [
            'message_id' => $result['message_id'],
            'chat' => $result['chat'],
            'date' => $result['date'],
        ];
    }

    public function getSampleOutput(): array
    {
        return [
            'message_id' => 456,
            'chat' => [
                'id' => -100123456789,
                'type' => 'group',
                'title' => 'My Group',
            ],
            'date' => 1703500800,
        ];
    }
}

/**
 * Telegram Send Photo Action
 */
class TelegramSendPhotoAction extends \App\Services\Integration\Connector\AbstractAction
{
    public function getName(): string
    {
        return 'send_photo';
    }

    public function getDisplayName(): string
    {
        return 'Send Photo';
    }

    public function getDescription(): string
    {
        return 'Send a photo to a chat';
    }

    public function getGroup(): string
    {
        return 'media';
    }

    public function getInputFields(): array
    {
        return [
            'chat_id' => [
                'type' => 'string',
                'label' => 'Chat ID',
                'required' => true,
            ],
            'photo' => [
                'type' => 'string',
                'label' => 'Photo URL',
                'required' => true,
                'help' => 'URL of the photo to send',
            ],
            'caption' => [
                'type' => 'text',
                'label' => 'Caption',
                'required' => false,
            ],
        ];
    }

    public function getOutputFields(): array
    {
        return [
            'message_id' => ['type' => 'integer', 'label' => 'Message ID'],
            'photo' => ['type' => 'array', 'label' => 'Photo Sizes'],
        ];
    }

    public function execute(array $credentials, array $input): array
    {
        $botToken = $credentials['bot_token'] ?? '';

        $params = [
            'chat_id' => $input['chat_id'],
            'photo' => $input['photo'],
        ];

        if (!empty($input['caption'])) {
            $params['caption'] = $input['caption'];
        }

        $response = \Illuminate\Support\Facades\Http::post(
            "https://api.telegram.org/bot{$botToken}/sendPhoto",
            $params
        );

        if (!$response->json('ok')) {
            throw new \App\Exceptions\Integration\ApiRequestException(
                $response->json('description') ?? 'Failed to send photo',
                $response->status()
            );
        }

        $result = $response->json('result');

        return [
            'message_id' => $result['message_id'],
            'photo' => $result['photo'] ?? [],
        ];
    }

    public function getSampleOutput(): array
    {
        return [
            'message_id' => 789,
            'photo' => [
                ['file_id' => 'AgACAgIAAxkBAAI...', 'width' => 90, 'height' => 90],
                ['file_id' => 'AgACAgIAAxkBAAI...', 'width' => 320, 'height' => 320],
            ],
        ];
    }
}
