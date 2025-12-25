<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for the Mail Composer.
 *
 * Handles email template management and composition.
 */
interface MailComposerContract
{
    /**
     * Register an email template.
     *
     * @param string $name Template name
     * @param array $config Template configuration
     * @param string|null $pluginSlug Owner plugin
     * @return self
     */
    public function registerTemplate(string $name, array $config, ?string $pluginSlug = null): self;

    /**
     * Get a template by name.
     *
     * @param string $name Template name
     * @return array|null
     */
    public function getTemplate(string $name): ?array;

    /**
     * Get all templates.
     *
     * @return Collection
     */
    public function getTemplates(): Collection;

    /**
     * Compose an email from template.
     *
     * @param string $template Template name
     * @param array $data Template variables
     * @param array $options Additional options
     * @return array Composed email (subject, body, etc.)
     */
    public function compose(string $template, array $data = [], array $options = []): array;

    /**
     * Send an email.
     *
     * @param string $to Recipient email
     * @param string $template Template name
     * @param array $data Template variables
     * @param array $options Send options (cc, bcc, attachments, etc.)
     * @return bool
     */
    public function send(string $to, string $template, array $data = [], array $options = []): bool;

    /**
     * Queue an email for later sending.
     *
     * @param string $to Recipient email
     * @param string $template Template name
     * @param array $data Template variables
     * @param array $options Send options
     * @return string Queue job ID
     */
    public function queue(string $to, string $template, array $data = [], array $options = []): string;

    /**
     * Preview an email without sending.
     *
     * @param string $template Template name
     * @param array $data Template variables
     * @return array Preview data (subject, html, text)
     */
    public function preview(string $template, array $data = []): array;

    /**
     * Get email sending statistics.
     *
     * @param string|null $template Filter by template
     * @param array $dateRange Date range filter
     * @return array Statistics
     */
    public function getStatistics(?string $template = null, array $dateRange = []): array;
}
