<?php

declare(strict_types=1);

namespace App\Contracts\Integration;

/**
 * Contract for Data Transformer.
 *
 * Handles data transformation between nodes in a flow.
 * Includes:
 * - Field mapping
 * - Type conversion
 * - Expression evaluation
 * - Data formatting
 */
interface DataTransformerContract
{
    // =========================================================================
    // TRANSFORMATION
    // =========================================================================

    /**
     * Transform data using a mapping.
     *
     * @param array $data Source data
     * @param array $mapping Field mappings
     * @param array $context Additional context
     * @return array Transformed data
     */
    public function transform(array $data, array $mapping, array $context = []): array;

    /**
     * Map a single field value.
     *
     * @param mixed $value Source value
     * @param array $fieldConfig Field configuration
     * @param array $context Additional context
     * @return mixed Mapped value
     */
    public function mapField(mixed $value, array $fieldConfig, array $context = []): mixed;

    // =========================================================================
    // EXPRESSIONS
    // =========================================================================

    /**
     * Evaluate an expression.
     *
     * @param string $expression Expression to evaluate
     * @param array $data Available data
     * @return mixed Result
     */
    public function evaluate(string $expression, array $data): mixed;

    /**
     * Parse expression and extract variable references.
     *
     * @param string $expression Expression
     * @return array Variable paths
     */
    public function parseExpression(string $expression): array;

    /**
     * Validate an expression.
     *
     * @param string $expression Expression
     * @return array Validation errors
     */
    public function validateExpression(string $expression): array;

    // =========================================================================
    // TYPE CONVERSION
    // =========================================================================

    /**
     * Convert value to specified type.
     *
     * @param mixed $value Value to convert
     * @param string $type Target type
     * @param array $options Conversion options
     * @return mixed Converted value
     */
    public function convert(mixed $value, string $type, array $options = []): mixed;

    /**
     * Get available type converters.
     *
     * @return array
     */
    public function getConverters(): array;

    /**
     * Register a custom type converter.
     *
     * @param string $fromType Source type
     * @param string $toType Target type
     * @param callable $converter Converter function
     * @return void
     */
    public function registerConverter(string $fromType, string $toType, callable $converter): void;

    // =========================================================================
    // FORMATTING
    // =========================================================================

    /**
     * Format a value.
     *
     * @param mixed $value Value to format
     * @param string $format Format pattern
     * @param array $options Formatting options
     * @return string Formatted value
     */
    public function format(mixed $value, string $format, array $options = []): string;

    /**
     * Parse a formatted string.
     *
     * @param string $value Formatted value
     * @param string $format Format pattern
     * @return mixed Parsed value
     */
    public function parse(string $value, string $format): mixed;

    // =========================================================================
    // DATA ACCESS
    // =========================================================================

    /**
     * Get value from nested data using dot notation.
     *
     * @param array $data Data array
     * @param string $path Dot notation path
     * @param mixed $default Default value
     * @return mixed
     */
    public function getValue(array $data, string $path, mixed $default = null): mixed;

    /**
     * Set value in nested data using dot notation.
     *
     * @param array $data Data array
     * @param string $path Dot notation path
     * @param mixed $value Value to set
     * @return array Modified data
     */
    public function setValue(array $data, string $path, mixed $value): array;

    // =========================================================================
    // BUILT-IN FUNCTIONS
    // =========================================================================

    /**
     * Get available functions.
     *
     * @return array Function definitions
     */
    public function getFunctions(): array;

    /**
     * Register a custom function.
     *
     * @param string $name Function name
     * @param callable $handler Function handler
     * @param array $definition Function definition
     * @return void
     */
    public function registerFunction(string $name, callable $handler, array $definition = []): void;

    /**
     * Call a registered function.
     *
     * @param string $name Function name
     * @param array $arguments Function arguments
     * @return mixed Result
     */
    public function callFunction(string $name, array $arguments): mixed;

    // =========================================================================
    // SCHEMA
    // =========================================================================

    /**
     * Infer schema from data.
     *
     * @param array $data Sample data
     * @return array Schema definition
     */
    public function inferSchema(array $data): array;

    /**
     * Validate data against schema.
     *
     * @param array $data Data to validate
     * @param array $schema Schema definition
     * @return array Validation errors
     */
    public function validateSchema(array $data, array $schema): array;
}
