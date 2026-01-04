<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Str;
use VodoCommerce\Models\CustomerGroup;
use VodoCommerce\Models\Store;

class CustomerGroupService
{
    public function __construct(protected Store $store)
    {
    }

    public function create(array $data): CustomerGroup
    {
        $data['store_id'] = $this->store->id;

        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        $group = CustomerGroup::create($data);

        do_action('commerce.customer_group.created', $group);

        return $group;
    }

    public function update(CustomerGroup $group, array $data): CustomerGroup
    {
        $group->update($data);

        do_action('commerce.customer_group.updated', $group);

        return $group->fresh();
    }

    public function delete(CustomerGroup $group): bool
    {
        $group->delete();

        do_action('commerce.customer_group.deleted', $group);

        return true;
    }

    public function addCustomer(CustomerGroup $group, int $customerId): void
    {
        $group->customers()->attach($customerId, ['joined_at' => now()]);

        do_action('commerce.customer_group.customer_added', $group, $customerId);
    }

    public function removeCustomer(CustomerGroup $group, int $customerId): void
    {
        $group->customers()->detach($customerId);

        do_action('commerce.customer_group.customer_removed', $group, $customerId);
    }

    protected function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (CustomerGroup::where('store_id', $this->store->id)->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }
}
