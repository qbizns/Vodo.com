<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Str;
use VodoCommerce\Models\Affiliate;
use VodoCommerce\Models\AffiliateCommission;
use VodoCommerce\Models\AffiliateLink;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\Store;

class AffiliateService
{
    public function __construct(protected Store $store)
    {
    }

    public function create(Customer $customer, array $data): Affiliate
    {
        $data['store_id'] = $this->store->id;
        $data['customer_id'] = $customer->id;

        if (empty($data['code'])) {
            $data['code'] = $this->generateUniqueCode();
        }

        $affiliate = Affiliate::create($data);

        do_action('commerce.affiliate.created', $affiliate);

        return $affiliate;
    }

    public function update(Affiliate $affiliate, array $data): Affiliate
    {
        $affiliate->update($data);

        do_action('commerce.affiliate.updated', $affiliate);

        return $affiliate->fresh();
    }

    public function approve(Affiliate $affiliate): Affiliate
    {
        $affiliate->approved_at = now();
        $affiliate->is_active = true;
        $affiliate->save();

        do_action('commerce.affiliate.approved', $affiliate);

        return $affiliate;
    }

    public function createLink(Affiliate $affiliate, array $data): AffiliateLink
    {
        $link = $affiliate->links()->create($data);

        do_action('commerce.affiliate.link_created', $link);

        return $link;
    }

    public function trackClick(AffiliateLink $link): void
    {
        $link->incrementClick();

        do_action('commerce.affiliate.click_tracked', $link);
    }

    public function createCommission(Affiliate $affiliate, Order $order, ?AffiliateLink $link = null): AffiliateCommission
    {
        $commissionAmount = $affiliate->calculateCommission($order->total);

        $commission = $affiliate->commissions()->create([
            'order_id' => $order->id,
            'link_id' => $link?->id,
            'order_amount' => $order->total,
            'commission_amount' => $commissionAmount,
            'commission_rate' => $affiliate->commission_rate,
            'status' => 'pending',
        ]);

        $affiliate->pending_balance += $commissionAmount;
        $affiliate->total_earnings += $commissionAmount;
        $affiliate->save();

        if ($link) {
            $link->incrementConversion();
        }

        do_action('commerce.affiliate.commission_created', $commission);

        return $commission;
    }

    public function approveCommission(AffiliateCommission $commission): AffiliateCommission
    {
        $commission->approve();

        do_action('commerce.affiliate.commission_approved', $commission);

        return $commission;
    }

    public function payCommission(AffiliateCommission $commission): AffiliateCommission
    {
        $commission->markAsPaid();

        do_action('commerce.affiliate.commission_paid', $commission);

        return $commission;
    }

    protected function generateUniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Affiliate::where('code', $code)->exists());

        return $code;
    }
}
