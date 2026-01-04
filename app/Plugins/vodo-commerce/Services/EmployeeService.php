<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use VodoCommerce\Models\Employee;
use VodoCommerce\Models\Store;

class EmployeeService
{
    public function __construct(protected Store $store)
    {
    }

    public function create(array $data): Employee
    {
        $data['store_id'] = $this->store->id;

        $employee = Employee::create($data);

        do_action('commerce.employee.created', $employee);

        return $employee;
    }

    public function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);

        do_action('commerce.employee.updated', $employee);

        return $employee->fresh();
    }

    public function delete(Employee $employee): bool
    {
        $employee->delete();

        do_action('commerce.employee.deleted', $employee);

        return true;
    }

    public function activate(Employee $employee): Employee
    {
        $employee->is_active = true;
        $employee->save();

        do_action('commerce.employee.activated', $employee);

        return $employee;
    }

    public function deactivate(Employee $employee): Employee
    {
        $employee->is_active = false;
        $employee->save();

        do_action('commerce.employee.deactivated', $employee);

        return $employee;
    }

    public function updatePermissions(Employee $employee, array $permissions): Employee
    {
        $employee->permissions = $permissions;
        $employee->save();

        do_action('commerce.employee.permissions_updated', $employee);

        return $employee;
    }

    public function changeRole(Employee $employee, string $role): Employee
    {
        $employee->role = $role;
        $employee->save();

        do_action('commerce.employee.role_changed', $employee);

        return $employee;
    }
}
