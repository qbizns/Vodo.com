<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Discount;
use VodoCommerce\Models\PromotionRule;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<PromotionRule>
 */
class PromotionRuleFactory extends Factory
{
    protected $model = PromotionRule::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'discount_id' => Discount::factory(),
            'rule_type' => PromotionRule::RULE_CART_SUBTOTAL,
            'operator' => PromotionRule::OPERATOR_GREATER_THAN,
            'value' => '100',
            'metadata' => [],
            'position' => 0,
        ];
    }

    public function cartSubtotal(string $operator = PromotionRule::OPERATOR_GREATER_THAN, string $value = '100'): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_type' => PromotionRule::RULE_CART_SUBTOTAL,
            'operator' => $operator,
            'value' => $value,
        ]);
    }

    public function cartQuantity(string $operator = PromotionRule::OPERATOR_GREATER_THAN_OR_EQUAL, string $value = '5'): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_type' => PromotionRule::RULE_CART_QUANTITY,
            'operator' => $operator,
            'value' => $value,
        ]);
    }

    public function productQuantity(int $productId, string $operator = PromotionRule::OPERATOR_GREATER_THAN_OR_EQUAL, string $value = '2'): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_type' => PromotionRule::RULE_PRODUCT_QUANTITY,
            'operator' => $operator,
            'value' => $value,
            'metadata' => ['product_id' => $productId],
        ]);
    }

    public function categoryQuantity(int $categoryId, string $operator = PromotionRule::OPERATOR_GREATER_THAN_OR_EQUAL, string $value = '3'): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_type' => PromotionRule::RULE_CATEGORY_QUANTITY,
            'operator' => $operator,
            'value' => $value,
            'metadata' => ['category_id' => $categoryId],
        ]);
    }

    public function customerGroup(array $groupIds): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_type' => PromotionRule::RULE_CUSTOMER_GROUP,
            'operator' => PromotionRule::OPERATOR_IN,
            'value' => implode(',', $groupIds),
        ]);
    }

    public function shippingCountry(string|array $countries): static
    {
        $countryValue = is_array($countries) ? implode(',', $countries) : $countries;

        return $this->state(fn (array $attributes) => [
            'rule_type' => PromotionRule::RULE_SHIPPING_COUNTRY,
            'operator' => is_array($countries) ? PromotionRule::OPERATOR_IN : PromotionRule::OPERATOR_EQUALS,
            'value' => $countryValue,
        ]);
    }

    public function dayOfWeek(array $days): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_type' => PromotionRule::RULE_DAY_OF_WEEK,
            'operator' => PromotionRule::OPERATOR_IN,
            'value' => implode(',', $days),
        ]);
    }
}
