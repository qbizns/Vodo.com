<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Contracts\MailComposerContract;
use App\Models\EmailTemplate;
use App\Models\EmailLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * Mail Composer
 *
 * Handles email template management, composition, and sending.
 * Supports variable interpolation, layouts, and tracking.
 *
 * @example Register a template
 * ```php
 * $composer->registerTemplate('welcome', [
 *     'subject' => 'Welcome to {company_name}!',
 *     'layout' => 'emails.layouts.default',
 *     'body' => 'emails.welcome',
 *     'variables' => ['user_name', 'company_name', 'login_url'],
 * ]);
 * ```
 *
 * @example Send an email
 * ```php
 * $composer->send('customer@example.com', 'welcome', [
 *     'user_name' => 'John',
 *     'company_name' => 'Acme Inc',
 *     'login_url' => 'https://example.com/login',
 * ]);
 * ```
 */
class MailComposer implements MailComposerContract
{
    /**
     * Registered templates.
     *
     * @var array<string, array>
     */
    protected array $templates = [];

    /**
     * Plugin ownership.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Default template configuration.
     */
    protected array $defaultConfig = [
        'subject' => '',
        'layout' => null,
        'body' => null,
        'html' => null,
        'text' => null,
        'variables' => [],
        'from_name' => null,
        'from_email' => null,
        'reply_to' => null,
        'track_opens' => true,
        'track_clicks' => true,
    ];

    public function registerTemplate(string $name, array $config, ?string $pluginSlug = null): self
    {
        $this->templates[$name] = array_merge($this->defaultConfig, $config, [
            'name' => $name,
        ]);

        if ($pluginSlug) {
            $this->pluginOwnership[$name] = $pluginSlug;
        }

        // Persist to database for UI management
        EmailTemplate::updateOrCreate(
            ['slug' => $name],
            [
                'name' => $config['label'] ?? $this->generateLabel($name),
                'description' => $config['description'] ?? null,
                'subject' => $config['subject'] ?? '',
                'body_html' => $config['html'] ?? null,
                'body_text' => $config['text'] ?? null,
                'layout' => $config['layout'] ?? null,
                'variables' => $config['variables'] ?? [],
                'config' => $config,
                'plugin_slug' => $pluginSlug,
                'is_system' => $config['is_system'] ?? false,
            ]
        );

        return $this;
    }

    public function getTemplate(string $name): ?array
    {
        if (isset($this->templates[$name])) {
            return $this->templates[$name];
        }

        // Try loading from database
        $template = EmailTemplate::where('slug', $name)->first();
        if ($template) {
            return array_merge($this->defaultConfig, $template->config ?? [], [
                'name' => $name,
                'subject' => $template->subject,
                'html' => $template->body_html,
                'text' => $template->body_text,
                'layout' => $template->layout,
                'variables' => $template->variables,
            ]);
        }

        return null;
    }

    public function getTemplates(): Collection
    {
        $dbTemplates = EmailTemplate::all()->keyBy('slug')->map(fn($template) => array_merge(
            $this->defaultConfig,
            $template->config ?? [],
            [
                'name' => $template->slug,
                'subject' => $template->subject,
                'html' => $template->body_html,
                'text' => $template->body_text,
                'layout' => $template->layout,
                'variables' => $template->variables,
            ]
        ));

        return collect($this->templates)->merge($dbTemplates);
    }

    public function compose(string $template, array $data = [], array $options = []): array
    {
        $config = $this->getTemplate($template);

        if (!$config) {
            throw new \InvalidArgumentException("Email template not found: {$template}");
        }

        // Merge default data
        $data = array_merge($this->getDefaultData(), $data);

        // Render subject
        $subject = $this->parseVariables($config['subject'], $data);

        // Render HTML body
        $html = $this->renderBody($config, $data, 'html');

        // Render text body
        $text = $this->renderBody($config, $data, 'text');

        // Apply layout if specified
        if ($layout = $config['layout'] ?? null) {
            $html = $this->applyLayout($layout, $html, $data);
        }

        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
            'from_name' => $config['from_name'] ?? config('mail.from.name'),
            'from_email' => $config['from_email'] ?? config('mail.from.address'),
            'reply_to' => $config['reply_to'] ?? null,
        ];
    }

    public function send(string $to, string $template, array $data = [], array $options = []): bool
    {
        $composed = $this->compose($template, $data, $options);

        try {
            Mail::send([], [], function ($message) use ($to, $composed, $options) {
                $message->to($to)
                    ->subject($composed['subject'])
                    ->html($composed['html']);

                if ($composed['text']) {
                    $message->text($composed['text']);
                }

                if ($from = $composed['from_email']) {
                    $message->from($from, $composed['from_name']);
                }

                if ($replyTo = $composed['reply_to']) {
                    $message->replyTo($replyTo);
                }

                // Handle CC
                if ($cc = $options['cc'] ?? null) {
                    $message->cc($cc);
                }

                // Handle BCC
                if ($bcc = $options['bcc'] ?? null) {
                    $message->bcc($bcc);
                }

                // Handle attachments
                foreach ($options['attachments'] ?? [] as $attachment) {
                    if (is_string($attachment)) {
                        $message->attach($attachment);
                    } else {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? null,
                            'mime' => $attachment['mime'] ?? null,
                        ]);
                    }
                }
            });

            // Log the email
            $this->logEmail($to, $template, $data, 'sent');

            // Fire hook
            do_action('email_sent', $template, $to, $data);

            return true;
        } catch (\Exception $e) {
            // Log the failure
            $this->logEmail($to, $template, $data, 'failed', $e->getMessage());

            throw $e;
        }
    }

    public function queue(string $to, string $template, array $data = [], array $options = []): string
    {
        $jobId = Str::uuid()->toString();

        Queue::push(new SendEmailJob($to, $template, $data, $options, $jobId));

        // Log as queued
        $this->logEmail($to, $template, $data, 'queued', null, $jobId);

        return $jobId;
    }

    public function preview(string $template, array $data = []): array
    {
        return $this->compose($template, $data);
    }

    public function getStatistics(?string $template = null, array $dateRange = []): array
    {
        $query = EmailLog::query();

        if ($template) {
            $query->where('template', $template);
        }

        if (!empty($dateRange)) {
            $query->whereBetween('created_at', $dateRange);
        }

        return [
            'total' => $query->count(),
            'sent' => (clone $query)->where('status', 'sent')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'opened' => (clone $query)->whereNotNull('opened_at')->count(),
            'clicked' => (clone $query)->whereNotNull('clicked_at')->count(),
            'by_template' => (clone $query)->selectRaw('template, count(*) as count')
                ->groupBy('template')
                ->pluck('count', 'template')
                ->toArray(),
        ];
    }

    /**
     * Render body content.
     */
    protected function renderBody(array $config, array $data, string $type): ?string
    {
        $content = $config[$type] ?? null;

        if (!$content) {
            // Try body view
            if ($bodyView = $config['body'] ?? null) {
                if (View::exists($bodyView)) {
                    return View::make($bodyView, $data)->render();
                }
            }
            return null;
        }

        // If it's a view path
        if (View::exists($content)) {
            return View::make($content, $data)->render();
        }

        // Otherwise treat as inline template
        return $this->parseVariables($content, $data);
    }

    /**
     * Apply layout to content.
     */
    protected function applyLayout(string $layout, string $content, array $data): string
    {
        if (!View::exists($layout)) {
            return $content;
        }

        return View::make($layout, array_merge($data, ['content' => $content]))->render();
    }

    /**
     * Parse variables in template string.
     */
    protected function parseVariables(string $template, array $data): string
    {
        return preg_replace_callback('/\{(\w+(?:\.\w+)*)\}/', function ($matches) use ($data) {
            return data_get($data, $matches[1], $matches[0]);
        }, $template);
    }

    /**
     * Get default template data.
     */
    protected function getDefaultData(): array
    {
        return [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'current_year' => date('Y'),
        ];
    }

    /**
     * Generate a human-readable label from template name.
     */
    protected function generateLabel(string $name): string
    {
        return Str::title(str_replace(['.', '_', '-'], ' ', $name));
    }

    /**
     * Log email to database.
     */
    protected function logEmail(
        string $to,
        string $template,
        array $data,
        string $status,
        ?string $error = null,
        ?string $jobId = null
    ): void {
        EmailLog::create([
            'to' => $to,
            'template' => $template,
            'subject' => $data['subject'] ?? null,
            'status' => $status,
            'error' => $error,
            'job_id' => $jobId,
            'metadata' => $data,
        ]);
    }

    /**
     * Get templates by category.
     *
     * @param string $category Category name
     * @return Collection
     */
    public function getByCategory(string $category): Collection
    {
        return $this->getTemplates()->filter(function ($config) use ($category) {
            return ($config['category'] ?? null) === $category;
        });
    }

    /**
     * Duplicate a template.
     *
     * @param string $source Source template name
     * @param string $newName New template name
     * @return self
     */
    public function duplicate(string $source, string $newName): self
    {
        $config = $this->getTemplate($source);

        if (!$config) {
            throw new \InvalidArgumentException("Template not found: {$source}");
        }

        unset($config['name']);

        return $this->registerTemplate($newName, $config);
    }
}
