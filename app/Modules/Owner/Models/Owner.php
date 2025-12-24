<?php

namespace App\Modules\Owner\Models;

use App\Models\User;

/**
 * @deprecated This class is deprecated. Use App\Models\User with 'owner' role instead.
 * 
 * Owner panel access is now controlled via roles:
 * - Users with 'super_admin', 'console_admin', or 'owner' role can access the Owner panel
 * - Use User::canAccessOwner() to check panel access
 * - Business owners should have company_name field set
 * 
 * @see \App\Models\User
 */
class Owner extends User
{
    /**
     * @deprecated Use User model with owner role instead
     */
    public function __construct(array $attributes = [])
    {
        trigger_error(
            'Owner model is deprecated. Use App\Models\User with owner role instead.',
            E_USER_DEPRECATED
        );
        parent::__construct($attributes);
    }
}

