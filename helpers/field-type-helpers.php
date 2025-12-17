<?php

/**
 * Field Type System Global Helpers
 * 
 * These helper functions provide easy access to field type functionality
 * without needing to inject the registry service.
 */

use App\Models\FieldType;
use App\Services\Field\FieldTypeRegistry;
use App\Contracts\FieldTypeContract;

if (!function_exists('field_type_registry')) {
    /**
     * Get the field type registry instance
     */
    function field_type_registry(): FieldTypeRegistry
    {
        return app(FieldTypeRegistry::class);
    }
}

// =========================================================================
// Registration
// =========================================================================

if (!function_exists('register_field_type')) {
    /**
     * Register a custom field type
     * 
     * @param string $handlerClass Fully qualified handler class name
     * @param string|null $pluginSlug Plugin slug for ownership
     * @param bool $system Whether this is a system field type
     * @return FieldType
     */
    function register_field_type(string $handlerClass, ?string $pluginSlug = null, bool $system = false): FieldType
    {
        return field_type_registry()->register($handlerClass, $pluginSlug, $system);
    }
}

if (!function_exists('unregister_field_type')) {
    /**
     * Unregister a field type
     * 
     * @param string $name Field type name
     * @param string|null $pluginSlug Plugin slug for ownership verification
     * @return bool
     */
    function unregister_field_type(string $name, ?string $pluginSlug = null): bool
    {
        return field_type_registry()->unregister($name, $pluginSlug);
    }
}

// =========================================================================
// Retrieval
// =========================================================================

if (!function_exists('get_field_type')) {
    /**
     * Get a field type by name
     * 
     * @param string $name Field type name
     * @return FieldType|null
     */
    function get_field_type(string $name): ?FieldType
    {
        return field_type_registry()->get($name);
    }
}

if (!function_exists('get_field_type_handler')) {
    /**
     * Get the handler for a field type
     * 
     * @param string $name Field type name
     * @return FieldTypeContract|null
     */
    function get_field_type_handler(string $name): ?FieldTypeContract
    {
        return field_type_registry()->getHandler($name);
    }
}

if (!function_exists('field_type_exists')) {
    /**
     * Check if a field type exists
     * 
     * @param string $name Field type name
     * @return bool
     */
    function field_type_exists(string $name): bool
    {
        return field_type_registry()->exists($name);
    }
}

if (!function_exists('get_all_field_types')) {
    /**
     * Get all active field types
     * 
     * @return \Illuminate\Support\Collection
     */
    function get_all_field_types(): \Illuminate\Support\Collection
    {
        return field_type_registry()->all();
    }
}

if (!function_exists('get_field_types_by_category')) {
    /**
     * Get field types by category
     * 
     * @param string $category Category name
     * @return \Illuminate\Support\Collection
     */
    function get_field_types_by_category(string $category): \Illuminate\Support\Collection
    {
        return field_type_registry()->getByCategory($category);
    }
}

if (!function_exists('get_field_type_categories')) {
    /**
     * Get all available field type categories
     * 
     * @return array
     */
    function get_field_type_categories(): array
    {
        return field_type_registry()->getCategories();
    }
}

// =========================================================================
// Validation
// =========================================================================

if (!function_exists('get_field_validation_rules')) {
    /**
     * Get validation rules for a field type
     * 
     * @param string $typeName Field type name
     * @param array $fieldConfig Field configuration
     * @param array $context Additional context
     * @return array Laravel validation rules
     */
    function get_field_validation_rules(string $typeName, array $fieldConfig = [], array $context = []): array
    {
        return field_type_registry()->getValidationRules($typeName, $fieldConfig, $context);
    }
}

if (!function_exists('validate_field_value')) {
    /**
     * Validate a value against a field type
     * 
     * @param string $typeName Field type name
     * @param mixed $value Value to validate
     * @param array $fieldConfig Field configuration
     * @param array $context Additional context
     * @return bool|array True if valid, array of errors if invalid
     */
    function validate_field_value(string $typeName, $value, array $fieldConfig = [], array $context = []): bool|array
    {
        return field_type_registry()->validate($typeName, $value, $fieldConfig, $context);
    }
}

// =========================================================================
// Casting
// =========================================================================

if (!function_exists('cast_field_for_storage')) {
    /**
     * Cast a value for storage
     * 
     * @param string $typeName Field type name
     * @param mixed $value Value to cast
     * @param array $fieldConfig Field configuration
     * @return mixed Casted value
     */
    function cast_field_for_storage(string $typeName, $value, array $fieldConfig = [])
    {
        return field_type_registry()->castForStorage($typeName, $value, $fieldConfig);
    }
}

if (!function_exists('cast_field_from_storage')) {
    /**
     * Cast a value from storage
     * 
     * @param string $typeName Field type name
     * @param mixed $value Stored value
     * @param array $fieldConfig Field configuration
     * @return mixed Casted value
     */
    function cast_field_from_storage(string $typeName, $value, array $fieldConfig = [])
    {
        return field_type_registry()->castFromStorage($typeName, $value, $fieldConfig);
    }
}

// =========================================================================
// Formatting
// =========================================================================

if (!function_exists('format_field_value')) {
    /**
     * Format a field value for display
     * 
     * @param string $typeName Field type name
     * @param mixed $value Value to format
     * @param array $fieldConfig Field configuration
     * @param string $format Display format
     * @return string Formatted value
     */
    function format_field_value(string $typeName, $value, array $fieldConfig = [], string $format = 'default'): string
    {
        return field_type_registry()->formatForDisplay($typeName, $value, $fieldConfig, $format);
    }
}

// =========================================================================
// Convenience Functions for Common Types
// =========================================================================

if (!function_exists('format_money')) {
    /**
     * Format a value as money
     * 
     * @param mixed $value Value to format
     * @param array $config Optional configuration overrides
     * @return string Formatted money string
     */
    function format_money($value, array $config = []): string
    {
        $defaultConfig = [
            'currency' => config('field-types.money.default_currency', 'USD'),
            'currency_symbol' => config('field-types.money.default_symbol', '$'),
            'decimal_places' => config('field-types.money.decimal_places', 2),
            'thousand_separator' => config('field-types.money.thousand_separator', ','),
            'decimal_separator' => config('field-types.money.decimal_separator', '.'),
            'symbol_position' => config('field-types.money.symbol_position', 'before'),
        ];

        return format_field_value('money', $value, array_merge($defaultConfig, $config));
    }
}

if (!function_exists('format_date')) {
    /**
     * Format a value as date
     * 
     * @param mixed $value Value to format
     * @param string|null $format Date format
     * @return string Formatted date string
     */
    function format_date($value, ?string $format = null): string
    {
        $config = [
            'format' => $format ?? config('field-types.datetime.display_date_format', 'M j, Y'),
        ];

        return format_field_value('date', $value, $config);
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format a value as datetime
     * 
     * @param mixed $value Value to format
     * @param string|null $format DateTime format
     * @return string Formatted datetime string
     */
    function format_datetime($value, ?string $format = null): string
    {
        $config = [
            'format' => $format ?? config('field-types.datetime.display_datetime_format', 'M j, Y g:i A'),
        ];

        return format_field_value('datetime', $value, $config);
    }
}

if (!function_exists('format_rating')) {
    /**
     * Format a value as rating stars
     * 
     * @param mixed $value Rating value
     * @param int $max Maximum rating
     * @return string Formatted rating
     */
    function format_rating($value, int $max = 5): string
    {
        return format_field_value('rating', $value, ['max' => $max]);
    }
}

if (!function_exists('format_address')) {
    /**
     * Format an address array for display
     * 
     * @param array $value Address data
     * @param string $format 'default' or 'single_line'
     * @return string Formatted address
     */
    function format_address(array $value, string $format = 'default'): string
    {
        return format_field_value('address', $value, [], $format);
    }
}

if (!function_exists('format_location')) {
    /**
     * Format GPS coordinates for display
     * 
     * @param array $value Location data with lat/lng
     * @param string $format 'default' or 'dms'
     * @return string Formatted coordinates
     */
    function format_location(array $value, string $format = 'default'): string
    {
        return format_field_value('location', $value, [], $format);
    }
}

// =========================================================================
// Blade Directive Registration Helper
// =========================================================================

if (!function_exists('register_field_type_blade_directives')) {
    /**
     * Register Blade directives for field types
     * Call this from your service provider if needed
     */
    function register_field_type_blade_directives(): void
    {
        $blade = app('blade.compiler');

        // @fieldValue('type', $value, $config, 'format')
        $blade->directive('fieldValue', function ($expression) {
            return "<?php echo format_field_value({$expression}); ?>";
        });

        // @money($value, $config)
        $blade->directive('money', function ($expression) {
            return "<?php echo format_money({$expression}); ?>";
        });

        // @rating($value, $max)
        $blade->directive('rating', function ($expression) {
            return "<?php echo format_rating({$expression}); ?>";
        });
    }
}
