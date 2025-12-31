<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Models\Store;
use VodoCommerce\Registries\PaymentGatewayRegistry;
use VodoCommerce\Registries\ShippingCarrierRegistry;
use VodoCommerce\Registries\TaxProviderRegistry;

class SettingsController extends Controller
{
    public function general(Request $request)
    {
        $store = $this->getCurrentStore($request);

        return view('vodo-commerce::admin.settings.general', [
            'store' => $store,
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.settings.general');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'currency' => 'required|string|size:3',
            'timezone' => 'required|string|timezone',
            'logo' => 'nullable|string|max:255',
        ]);

        $store->update($validated);

        return back()->with('success', 'Store settings updated');
    }

    public function checkout(Request $request)
    {
        $store = $this->getCurrentStore($request);

        return view('vodo-commerce::admin.settings.checkout', [
            'store' => $store,
            'settings' => $store?->settings ?? [],
        ]);
    }

    public function updateCheckout(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.settings.checkout');
        }

        $validated = $request->validate([
            'enable_guest_checkout' => 'boolean',
            'require_phone' => 'boolean',
            'require_company' => 'boolean',
            'terms_url' => 'nullable|url',
            'privacy_url' => 'nullable|url',
        ]);

        $settings = $store->settings ?? [];
        $settings['checkout'] = $validated;
        $store->update(['settings' => $settings]);

        return back()->with('success', 'Checkout settings updated');
    }

    public function payments(Request $request)
    {
        $store = $this->getCurrentStore($request);
        $paymentGateways = app(PaymentGatewayRegistry::class);

        return view('vodo-commerce::admin.settings.payments', [
            'store' => $store,
            'gateways' => $paymentGateways->all(),
            'enabledGateways' => $store?->settings['payment_gateways'] ?? [],
        ]);
    }

    public function updatePayments(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.settings.payments');
        }

        $validated = $request->validate([
            'gateways' => 'array',
            'gateways.*' => 'string',
            'gateway_settings' => 'array',
        ]);

        $settings = $store->settings ?? [];
        $settings['payment_gateways'] = $validated['gateways'] ?? [];
        $settings['gateway_settings'] = $validated['gateway_settings'] ?? [];
        $store->update(['settings' => $settings]);

        return back()->with('success', 'Payment settings updated');
    }

    public function shipping(Request $request)
    {
        $store = $this->getCurrentStore($request);
        $shippingCarriers = app(ShippingCarrierRegistry::class);

        return view('vodo-commerce::admin.settings.shipping', [
            'store' => $store,
            'carriers' => $shippingCarriers->all(),
            'enabledCarriers' => $store?->settings['shipping_carriers'] ?? [],
        ]);
    }

    public function updateShipping(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.settings.shipping');
        }

        $validated = $request->validate([
            'carriers' => 'array',
            'carriers.*' => 'string',
            'carrier_settings' => 'array',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
        ]);

        $settings = $store->settings ?? [];
        $settings['shipping_carriers'] = $validated['carriers'] ?? [];
        $settings['carrier_settings'] = $validated['carrier_settings'] ?? [];
        $settings['free_shipping_threshold'] = $validated['free_shipping_threshold'];
        $store->update(['settings' => $settings]);

        return back()->with('success', 'Shipping settings updated');
    }

    public function taxes(Request $request)
    {
        $store = $this->getCurrentStore($request);
        $taxProviders = app(TaxProviderRegistry::class);

        return view('vodo-commerce::admin.settings.taxes', [
            'store' => $store,
            'providers' => $taxProviders->all(),
            'taxSettings' => $store?->settings['tax'] ?? [],
        ]);
    }

    public function updateTaxes(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.settings.taxes');
        }

        $validated = $request->validate([
            'tax_provider' => 'nullable|string',
            'prices_include_tax' => 'boolean',
            'calculate_tax_based_on' => 'in:billing,shipping',
            'tax_rates' => 'array',
        ]);

        $settings = $store->settings ?? [];
        $settings['tax'] = $validated;
        $store->update(['settings' => $settings]);

        return back()->with('success', 'Tax settings updated');
    }

    public function notifications(Request $request)
    {
        $store = $this->getCurrentStore($request);

        return view('vodo-commerce::admin.settings.notifications', [
            'store' => $store,
            'settings' => $store?->settings['notifications'] ?? [],
        ]);
    }

    public function updateNotifications(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.settings.notifications');
        }

        $validated = $request->validate([
            'order_confirmation' => 'boolean',
            'order_shipped' => 'boolean',
            'order_cancelled' => 'boolean',
            'low_stock_alert' => 'boolean',
            'low_stock_threshold' => 'integer|min:1',
            'admin_email' => 'nullable|email',
        ]);

        $settings = $store->settings ?? [];
        $settings['notifications'] = $validated;
        $store->update(['settings' => $settings]);

        return back()->with('success', 'Notification settings updated');
    }

    protected function getCurrentStore(Request $request): ?Store
    {
        $tenantId = $request->user()?->tenant_id;

        if (!$tenantId) {
            return null;
        }

        return Store::where('tenant_id', $tenantId)->first();
    }
}
