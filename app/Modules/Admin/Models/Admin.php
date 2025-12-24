<?php

namespace App\Modules\Admin\Models;

use App\Models\User;

/**
 * @deprecated This class is deprecated. Use App\Models\User with 'admin' role instead.
 * 
 * Admin panel access is now controlled via roles:
 * - Users with 'super_admin', 'console_admin', 'owner', or 'admin' role can access the Admin panel
 * - Use User::canAccessAdmin() to check panel access
 * 
 * @see \App\Models\User
 */
class Admin extends User
{
    /**
     * @deprecated Use User model with admin role instead
     */
    public function __construct(array $attributes = [])
    {
        trigger_error(
            'Admin model is deprecated. Use App\Models\User with admin role instead.',
            E_USER_DEPRECATED
        );
        parent::__construct($attributes);
    }
}

