<?php

use Illuminate\Validation\Rules\Password;

/**
 * Build password validation rules based on production config.
 *
 * This helper creates a comprehensive password validation rule that includes:
 * - Minimum length requirements
 * - Character complexity (uppercase, lowercase, numbers, symbols)
 * - Breach database check via Have I Been Pwned API (when enabled)
 *
 * @param bool|null $checkPwned Override the config setting for breach check
 * @return \Illuminate\Validation\Rules\Password
 */
function password_validation_rules(?bool $checkPwned = null): Password
{
    $config = config('production.security.password', []);
    
    $minLength = $config['min_length'] ?? 12;
    $requireUppercase = $config['require_uppercase'] ?? true;
    $requireLowercase = $config['require_lowercase'] ?? true;
    $requireNumbers = $config['require_numbers'] ?? true;
    $requireSymbols = $config['require_symbols'] ?? true;
    $checkBreached = $checkPwned ?? ($config['check_pwned'] ?? true);

    $rule = Password::min($minLength);

    if ($requireUppercase && $requireLowercase) {
        $rule->mixedCase();
    }

    if ($requireNumbers) {
        $rule->numbers();
    }

    if ($requireSymbols) {
        $rule->symbols();
    }

    // Check against Have I Been Pwned breach database
    if ($checkBreached) {
        $rule->uncompromised();
    }

    return $rule;
}

/**
 * Get password validation rules as an array for use in validation.
 *
 * @param bool $required Whether the password field is required
 * @param bool $confirmed Whether to require password confirmation
 * @param bool|null $checkPwned Override the config setting for breach check
 * @return array
 */
function get_password_rules(bool $required = true, bool $confirmed = true, ?bool $checkPwned = null): array
{
    $rules = [];
    
    if ($required) {
        $rules[] = 'required';
    } else {
        $rules[] = 'nullable';
    }
    
    $rules[] = 'string';
    $rules[] = password_validation_rules($checkPwned);
    
    if ($confirmed) {
        $rules[] = 'confirmed';
    }
    
    return $rules;
}

