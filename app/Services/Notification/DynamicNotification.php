<?php

declare(strict_types=1);

namespace App\Services\Notification;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * Dynamic Notification
 *
 * A flexible notification class that renders based on registered configuration.
 */
class DynamicNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Notification type name.
     */
    protected string $type;

    /**
     * Notification configuration.
     */
    protected array $config;

    /**
     * Notification data.
     */
    protected array $data;

    public function __construct(string $type, array $config, array $data)
    {
        $this->type = $type;
        $this->config = $config;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = $this->config['channels'] ?? ['database'];

        // Check user preferences if available
        if (method_exists($notifiable, 'getNotificationChannels')) {
            $userChannels = $notifiable->getNotificationChannels($this->type);
            if ($userChannels !== null) {
                $channels = $userChannels;
            }
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = new MailMessage();

        // Set subject
        $subject = $this->parseTemplate($this->config['subject'] ?? $this->type);
        $message->subject($subject);

        // Use template if provided
        if ($template = $this->config['template'] ?? null) {
            if (View::exists($template)) {
                $message->view($template, $this->data);
            } else {
                $message->markdown($template, $this->data);
            }
        } else {
            // Default formatting
            $message->line($this->data['message'] ?? 'You have a new notification.');

            if ($action = $this->config['action'] ?? null) {
                $message->action(
                    $action['label'] ?? 'View',
                    $this->parseTemplate($action['url'] ?? '#')
                );
            }
        }

        // Set priority
        if (($this->config['priority'] ?? 'normal') === 'high') {
            $message->priority(1);
        }

        return $message;
    }

    /**
     * Get the array representation for database storage.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->parseTemplate($this->config['title'] ?? $this->generateTitle()),
            'message' => $this->parseTemplate($this->config['message'] ?? ($this->data['message'] ?? '')),
            'icon' => $this->config['icon'] ?? 'bell',
            'color' => $this->config['color'] ?? 'blue',
            'priority' => $this->config['priority'] ?? 'normal',
            'action_url' => $this->parseTemplate($this->config['action']['url'] ?? null),
            'action_label' => $this->config['action']['label'] ?? null,
            'data' => $this->data,
        ];
    }

    /**
     * Get the broadcast representation.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Parse template string with data variables.
     */
    protected function parseTemplate(?string $template): ?string
    {
        if ($template === null) {
            return null;
        }

        return preg_replace_callback('/\{(\w+(?:\.\w+)*)\}/', function ($matches) {
            return data_get($this->data, $matches[1], $matches[0]);
        }, $template);
    }

    /**
     * Generate a title from the notification type.
     */
    protected function generateTitle(): string
    {
        return Str::title(str_replace(['.', '_', '-'], ' ', $this->type));
    }

    /**
     * Get the notification type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the notification data.
     */
    public function getData(): array
    {
        return $this->data;
    }
}
