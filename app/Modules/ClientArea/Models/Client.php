<?php

namespace App\Modules\ClientArea\Models;

use App\Models\User;

/**
 * @deprecated This class is deprecated. Use App\Models\User with 'client' role instead.
 * 
 * Client panel access is now controlled via roles:
 * - Any authenticated user can access the Client panel
 * - Use User::canAccessClient() to check panel access
 * 
 * @see \App\Models\User
 */
class Client extends User
{
    /**
     * @deprecated Use User model with client role instead
     */
    public function __construct(array $attributes = [])
    {
        trigger_error(
            'Client model is deprecated. Use App\Models\User with client role instead.',
            E_USER_DEPRECATED
        );
        parent::__construct($attributes);
    }
}

