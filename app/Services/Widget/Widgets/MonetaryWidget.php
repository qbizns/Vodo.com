<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class MonetaryWidget extends AbstractWidget
{
    protected string $name = 'monetary';
    protected string $label = 'Currency';
    protected array $supportedTypes = ['money', 'decimal', 'float'];
    protected string $component = 'widgets.monetary';
    protected array $defaultOptions = [
        'currency' => 'USD',
        'decimals' => 2,
        'symbol_position' => 'before',
    ];

    public function format(mixed $value, array $options = []): string
    {
        if ($value === null) {
            return '';
        }

        $currency = $options['currency'] ?? $this->defaultOptions['currency'];
        $decimals = $options['decimals'] ?? $this->defaultOptions['decimals'];
        $position = $options['symbol_position'] ?? $this->defaultOptions['symbol_position'];

        $formatted = number_format((float) $value, $decimals, '.', ',');
        $symbol = $this->getCurrencySymbol($currency);

        return $position === 'before'
            ? "{$symbol}{$formatted}"
            : "{$formatted} {$symbol}";
    }

    protected function getCurrencySymbol(string $currency): string
    {
        return match ($currency) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'SAR' => 'SAR',
            default => $currency,
        };
    }
}
