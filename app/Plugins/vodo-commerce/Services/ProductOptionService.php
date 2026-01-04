<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Database\Eloquent\Collection;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductOption;
use VodoCommerce\Models\ProductOptionTemplate;
use VodoCommerce\Models\ProductOptionValue;
use VodoCommerce\Models\Store;

class ProductOptionService
{
    public function __construct(protected Store $store)
    {
    }

    public function createOption(Product $product, array $data): ProductOption
    {
        $data['store_id'] = $this->store->id;
        $data['product_id'] = $product->id;

        if (empty($data['position'])) {
            $data['position'] = $product->options()->count();
        }

        $option = ProductOption::create($data);

        if (!empty($data['values']) && is_array($data['values'])) {
            $this->syncOptionValues($option, $data['values']);
        }

        do_action('commerce.product_option.created', $option, $product);

        return $option->load('values');
    }

    public function updateOption(ProductOption $option, array $data): ProductOption
    {
        $values = $data['values'] ?? null;
        unset($data['values']);

        $option->update($data);

        if ($values !== null && is_array($values)) {
            $this->syncOptionValues($option, $values);
        }

        do_action('commerce.product_option.updated', $option);

        return $option->fresh(['values']);
    }

    public function deleteOption(ProductOption $option): void
    {
        $option->values()->delete();
        $option->delete();

        do_action('commerce.product_option.deleted', $option);
    }

    public function syncOptionValues(ProductOption $option, array $values): void
    {
        $existingIds = $option->values()->pluck('id')->toArray();
        $updatedIds = [];

        foreach ($values as $index => $valueData) {
            $valueData['position'] = $index;

            if (!empty($valueData['id'])) {
                $value = $option->values()->find($valueData['id']);
                if ($value) {
                    $value->update($valueData);
                    $updatedIds[] = $value->id;
                }
            } else {
                $valueData['option_id'] = $option->id;
                $value = ProductOptionValue::create($valueData);
                $updatedIds[] = $value->id;
            }
        }

        // Delete removed values
        $toDelete = array_diff($existingIds, $updatedIds);
        if (!empty($toDelete)) {
            $option->values()->whereIn('id', $toDelete)->delete();
        }
    }

    public function getProductOptions(Product $product): Collection
    {
        return ProductOption::where('store_id', $this->store->id)
            ->where('product_id', $product->id)
            ->with(['values'])
            ->orderBy('position')
            ->get();
    }

    // Template Management

    public function createTemplate(array $data): ProductOptionTemplate
    {
        $data['store_id'] = $this->store->id;

        $template = ProductOptionTemplate::create($data);

        do_action('commerce.product_option_template.created', $template);

        return $template;
    }

    public function updateTemplate(ProductOptionTemplate $template, array $data): ProductOptionTemplate
    {
        $template->update($data);

        do_action('commerce.product_option_template.updated', $template);

        return $template->fresh();
    }

    public function deleteTemplate(ProductOptionTemplate $template): void
    {
        $template->delete();

        do_action('commerce.product_option_template.deleted', $template);
    }

    public function getTemplates(): Collection
    {
        return ProductOptionTemplate::where('store_id', $this->store->id)
            ->orderBy('name')
            ->get();
    }

    public function applyTemplate(Product $product, ProductOptionTemplate $template): Collection
    {
        $options = $template->options;

        if (!is_array($options)) {
            return new Collection();
        }

        $createdOptions = new Collection();

        foreach ($options as $optionData) {
            $option = $this->createOption($product, $optionData);
            $createdOptions->push($option);
        }

        do_action('commerce.product_option_template.applied', $template, $product);

        return $createdOptions;
    }

    public function reorderOptions(Product $product, array $optionIds): void
    {
        foreach ($optionIds as $position => $optionId) {
            ProductOption::where('id', $optionId)
                ->where('product_id', $product->id)
                ->where('store_id', $this->store->id)
                ->update(['position' => $position]);
        }
    }
}
