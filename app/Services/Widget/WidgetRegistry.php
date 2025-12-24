<?php

declare(strict_types=1);

namespace App\Services\Widget;

use App\Contracts\WidgetContract;
use App\Contracts\WidgetRegistryContract;
use Illuminate\Support\Collection;

/**
 * Widget Registry - Manages all registered widgets.
 *
 * Widgets are UI components that render and handle field data.
 * Each field type can have multiple widget options.
 *
 * @example Register a widget
 * ```php
 * $registry->register(new MonetaryWidget());
 * ```
 *
 * @example Get widgets for a field type
 * ```php
 * $widgets = $registry->getForType('decimal');
 * ```
 */
class WidgetRegistry implements WidgetRegistryContract
{
    /**
     * Registered widgets.
     *
     * @var array<string, WidgetContract>
     */
    protected array $widgets = [];

    /**
     * Default widgets per field type.
     *
     * @var array<string, string>
     */
    protected array $defaults = [];

    /**
     * Plugin ownership mapping.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Whether built-in widgets have been registered.
     */
    protected bool $initialized = false;

    public function __construct()
    {
        $this->registerBuiltInWidgets();
    }

    /**
     * Register built-in widgets.
     */
    protected function registerBuiltInWidgets(): void
    {
        if ($this->initialized) {
            return;
        }

        // Register all built-in widgets
        $this->register(new Widgets\CharWidget());
        $this->register(new Widgets\TextWidget());
        $this->register(new Widgets\HtmlWidget());
        $this->register(new Widgets\IntegerWidget());
        $this->register(new Widgets\FloatWidget());
        $this->register(new Widgets\MonetaryWidget());
        $this->register(new Widgets\BooleanWidget());
        $this->register(new Widgets\DateWidget());
        $this->register(new Widgets\DateTimeWidget());
        $this->register(new Widgets\SelectionWidget());
        $this->register(new Widgets\Many2OneWidget());
        $this->register(new Widgets\One2ManyWidget());
        $this->register(new Widgets\Many2ManyWidget());
        $this->register(new Widgets\ImageWidget());
        $this->register(new Widgets\FileWidget());
        $this->register(new Widgets\EmailWidget());
        $this->register(new Widgets\UrlWidget());
        $this->register(new Widgets\PhoneWidget());
        $this->register(new Widgets\ColorWidget());
        $this->register(new Widgets\JsonWidget());
        $this->register(new Widgets\TagsWidget());
        $this->register(new Widgets\ProgressBarWidget());
        $this->register(new Widgets\StatusBarWidget());
        $this->register(new Widgets\PriorityWidget());
        $this->register(new Widgets\BadgeWidget());

        // Set default widgets for field types
        $this->setDefault('string', 'char');
        $this->setDefault('text', 'text');
        $this->setDefault('html', 'html');
        $this->setDefault('integer', 'integer');
        $this->setDefault('decimal', 'float');
        $this->setDefault('float', 'float');
        $this->setDefault('money', 'monetary');
        $this->setDefault('boolean', 'boolean');
        $this->setDefault('date', 'date');
        $this->setDefault('datetime', 'datetime');
        $this->setDefault('select', 'selection');
        $this->setDefault('relation', 'many2one');
        $this->setDefault('image', 'image');
        $this->setDefault('file', 'file');
        $this->setDefault('email', 'email');
        $this->setDefault('url', 'url');
        $this->setDefault('phone', 'phone');
        $this->setDefault('color', 'color');
        $this->setDefault('json', 'json');

        $this->initialized = true;
    }

    public function register(WidgetContract $widget, ?string $pluginSlug = null): self
    {
        $name = $widget->getName();
        $this->widgets[$name] = $widget;

        if ($pluginSlug) {
            $this->pluginOwnership[$name] = $pluginSlug;
        }

        return $this;
    }

    public function unregister(string $name): bool
    {
        if (!isset($this->widgets[$name])) {
            return false;
        }

        unset($this->widgets[$name]);
        unset($this->pluginOwnership[$name]);

        // Remove from defaults if set
        $this->defaults = array_filter(
            $this->defaults,
            fn($widgetName) => $widgetName !== $name
        );

        return true;
    }

    public function get(string $name): ?WidgetContract
    {
        return $this->widgets[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->widgets[$name]);
    }

    public function all(): Collection
    {
        return collect($this->widgets);
    }

    public function getForType(string $fieldType): Collection
    {
        return $this->all()->filter(
            fn(WidgetContract $widget) => $widget->supports($fieldType)
        );
    }

    public function getDefault(string $fieldType): ?WidgetContract
    {
        $widgetName = $this->defaults[$fieldType] ?? null;

        if (!$widgetName) {
            return null;
        }

        return $this->get($widgetName);
    }

    public function setDefault(string $fieldType, string $widgetName): self
    {
        $this->defaults[$fieldType] = $widgetName;

        return $this;
    }

    public function format(string $widgetName, mixed $value, array $options = []): string
    {
        $widget = $this->get($widgetName);

        if (!$widget) {
            return (string) $value;
        }

        return $widget->format($value, array_merge($widget->getDefaultOptions(), $options));
    }

    public function parse(string $widgetName, mixed $value, array $options = []): mixed
    {
        $widget = $this->get($widgetName);

        if (!$widget) {
            return $value;
        }

        return $widget->parse($value, array_merge($widget->getDefaultOptions(), $options));
    }

    /**
     * Get all default widget mappings.
     *
     * @return array<string, string>
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Get widgets as options for select inputs.
     *
     * @param string|null $fieldType Filter by field type
     * @return array
     */
    public function asOptions(?string $fieldType = null): array
    {
        $widgets = $fieldType ? $this->getForType($fieldType) : $this->all();

        return $widgets
            ->map(fn(WidgetContract $widget) => [
                'value' => $widget->getName(),
                'label' => $widget->getLabel(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return $this->all()
            ->map(fn(WidgetContract $widget) => $widget->toArray())
            ->toArray();
    }
}
