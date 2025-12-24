<?php

declare(strict_types=1);

namespace App\Services\Action;

/**
 * Generic action created from array configuration.
 *
 * Used when registering actions via registerFromArray().
 */
class GenericAction extends AbstractAction
{
    /**
     * Handler callable.
     *
     * @var callable|null
     */
    protected $handler;

    /**
     * Guard callable.
     *
     * @var callable|null
     */
    protected $guardHandler;

    /**
     * Create a new GenericAction.
     *
     * @param string $name Action name
     * @param array $config Configuration
     */
    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->label = $config['label'] ?? ucfirst(str_replace('_', ' ', $name));
        $this->type = $config['type'] ?? 'server';
        $this->entity = $config['entity'] ?? null;
        $this->permissions = $config['permissions'] ?? [];
        $this->icon = $config['icon'] ?? 'play';
        $this->priority = $config['priority'] ?? 10;
        $this->destructive = $config['destructive'] ?? false;
        $this->handler = $config['handler'] ?? null;
        $this->guardHandler = $config['guard'] ?? null;
        $this->config = $config['config'] ?? [];
    }

    protected function guard(array $context = []): bool
    {
        if ($this->guardHandler) {
            return call_user_func($this->guardHandler, $context);
        }

        return parent::guard($context);
    }

    public function execute(array $context = []): mixed
    {
        if (!$this->handler) {
            throw new \RuntimeException("No handler defined for action: {$this->name}");
        }

        return call_user_func($this->handler, $context);
    }
}
