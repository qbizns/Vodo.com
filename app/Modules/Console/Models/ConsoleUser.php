<?php

namespace App\Modules\Console\Models;

use App\Models\User;

/**
 * @deprecated This class is deprecated. Use App\Models\User with 'console_admin' role instead.
 * 
 * Console panel access is now controlled via roles:
 * - Users with 'super_admin' or 'console_admin' role can access the Console panel
 * - Use User::canAccessConsole() to check panel access
 * 
 * @see \App\Models\User
 */
class ConsoleUser extends User
{
    /**
     * @deprecated Use User model with console_admin role instead
     */
    public function __construct(array $attributes = [])
    {
        trigger_error(
            'ConsoleUser is deprecated. Use App\Models\User with console_admin role instead.',
            E_USER_DEPRECATED
        );
        parent::__construct($attributes);
    }
}

