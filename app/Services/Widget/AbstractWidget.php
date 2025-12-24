<?php

declare(strict_types=1);

namespace App\Services\Widget;

use App\Contracts\WidgetContract;

/**
 * Abstract base class for widgets.
 *
 * Widgets are UI components that render and handle field data.
 */
abstract class AbstractWidget implements WidgetContract
{
    /**
     * Widget name.
     */
    protected string $name;

    /**
     * Human-readable label.
     */
    protected string $label;

    /**
     * Supported field types.
     *
     * @var array<string>
     */
    protected array $supportedTypes = [];

    /**
     * Blade component name.
     */
    protected string $component;

    /**
     * Default options.
     */
    protected array $defaultOptions = [];

    /**
     * JavaScript dependencies.
     *
     * @var array<string>
     */
    protected array $jsDependencies = [];

    /**
     * CSS dependencies.
     *
     * @var array<string>
     */
    protected array $cssDependencies = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSupportedTypes(): array
    {
        return $this->supportedTypes;
    }

    public function supports(string $type): bool
    {
        return in_array($type, $this->supportedTypes, true);
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function getDefaultOptions(): array
    {
        return $this->defaultOptions;
    }

    public function format(mixed $value, array $options = []): string
    {
        if ($value === null) {
            return '';
        }

        return (string) $value;
    }

    public function parse(mixed $value, array $options = []): mixed
    {
        return $value;
    }

    public function validate(mixed $value, array $options = []): array
    {
        return [];
    }

    public function getJsDependencies(): array
    {
        return $this->jsDependencies;
    }

    public function getCssDependencies(): array
    {
        return $this->cssDependencies;
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'supportedTypes' => $this->getSupportedTypes(),
            'component' => $this->getComponent(),
            'defaultOptions' => $this->getDefaultOptions(),
        ];
    }
}
