<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use VodoCommerce\Services\SandboxStoreProvisioner;

/**
 * Sandbox Store Controller
 *
 * API endpoints for managing sandbox stores for plugin developers.
 */
class SandboxController extends Controller
{
    public function __construct(
        protected SandboxStoreProvisioner $provisioner
    ) {
    }

    /**
     * Provision a new sandbox store.
     */
    public function provision(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'app_name' => 'required|string|max:100',
            'store_name' => 'nullable|string|max:100',
            'currency' => 'nullable|string|size:3',
            'timezone' => 'nullable|string|max:50',
            'expiry_days' => 'nullable|integer|min:1|max:90',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $tenantId = $request->user()?->tenant_id ?? 1;

        $result = $this->provisioner->provision(
            tenantId: $tenantId,
            developerEmail: $request->input('email'),
            appName: $request->input('app_name'),
            options: $request->only(['store_name', 'currency', 'timezone', 'expiry_days'])
        );

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json($result, 201);
    }

    /**
     * List sandbox stores for the authenticated developer.
     */
    public function index(Request $request): JsonResponse
    {
        $email = $request->input('email') ?? $request->user()?->email;

        if (!$email) {
            return response()->json([
                'success' => false,
                'error' => 'Email is required',
            ], 400);
        }

        $stores = $this->provisioner->listForDeveloper($email);

        return response()->json([
            'success' => true,
            'data' => $stores->map(fn ($store) => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'domain' => $store->domain,
                'currency' => $store->currency,
                'created_at' => $store->created_at->toIso8601String(),
                'expires_at' => $store->expires_at?->toIso8601String(),
                'is_expired' => $store->expires_at && $store->expires_at->isPast(),
            ]),
        ]);
    }

    /**
     * Get sandbox store details.
     */
    public function show(int $storeId): JsonResponse
    {
        $store = \VodoCommerce\Models\Store::where('id', $storeId)
            ->where('is_sandbox', true)
            ->first();

        if (!$store) {
            return response()->json([
                'success' => false,
                'error' => 'Sandbox store not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'domain' => $store->domain,
                'currency' => $store->currency,
                'timezone' => $store->timezone,
                'owner_email' => $store->owner_email,
                'created_at' => $store->created_at->toIso8601String(),
                'expires_at' => $store->expires_at?->toIso8601String(),
                'is_expired' => $store->expires_at && $store->expires_at->isPast(),
                'settings' => $store->settings,
                'stats' => [
                    'products' => $store->products()->count(),
                    'categories' => $store->categories()->count(),
                    'customers' => $store->customers()->count(),
                    'orders' => $store->orders()->count(),
                ],
                'api_base_url' => config('app.url') . '/api/v1/commerce',
                'documentation_url' => config('app.url') . '/api/docs/commerce',
            ],
        ]);
    }

    /**
     * Extend sandbox store expiry.
     */
    public function extend(Request $request, int $storeId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $days = $request->input('days', 30);

        if ($this->provisioner->extendExpiry($storeId, $days)) {
            $store = \VodoCommerce\Models\Store::find($storeId);

            return response()->json([
                'success' => true,
                'message' => "Store expiry extended by {$days} days",
                'expires_at' => $store->expires_at->toIso8601String(),
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Sandbox store not found',
        ], 404);
    }

    /**
     * Reset sandbox store data.
     */
    public function reset(int $storeId): JsonResponse
    {
        if ($this->provisioner->resetData($storeId)) {
            return response()->json([
                'success' => true,
                'message' => 'Sandbox store data has been reset',
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Sandbox store not found',
        ], 404);
    }

    /**
     * Delete a sandbox store.
     */
    public function destroy(int $storeId): JsonResponse
    {
        if ($this->provisioner->delete($storeId)) {
            return response()->json([
                'success' => true,
                'message' => 'Sandbox store deleted',
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Sandbox store not found',
        ], 404);
    }

    /**
     * Regenerate API credentials for a sandbox store.
     */
    public function regenerateCredentials(Request $request, int $storeId): JsonResponse
    {
        $store = \VodoCommerce\Models\Store::where('id', $storeId)
            ->where('is_sandbox', true)
            ->first();

        if (!$store) {
            return response()->json([
                'success' => false,
                'error' => 'Sandbox store not found',
            ], 404);
        }

        // Revoke existing OAuth applications for this sandbox
        \VodoCommerce\Models\OAuthApplication::where('store_id', $storeId)
            ->where('is_sandbox', true)
            ->update(['is_active' => false]);

        // Create new credentials
        $appName = $request->input('app_name', 'Sandbox App');
        $scopes = [
            'commerce.products.read',
            'commerce.products.write',
            'commerce.orders.read',
            'commerce.orders.write',
            'commerce.customers.read',
            'commerce.cart.read',
            'commerce.cart.write',
            'commerce.checkout.read',
            'commerce.checkout.write',
            'commerce.webhooks.read',
            'commerce.webhooks.write',
        ];

        try {
            $application = \VodoCommerce\Models\OAuthApplication::createWithCredentials([
                'name' => $appName . ' (Sandbox)',
                'type' => 'private',
                'developer_email' => $store->owner_email,
                'store_id' => $storeId,
                'redirect_uris' => [
                    'http://localhost:3000/callback',
                    'http://localhost:8080/callback',
                    'https://oauth.pstmn.io/v1/callback',
                ],
                'scopes' => $scopes,
                'is_sandbox' => true,
            ]);

            return response()->json([
                'success' => true,
                'credentials' => [
                    'oauth' => [
                        'client_id' => $application['client_id'],
                        'client_secret' => $application['client_secret'],
                        'authorization_url' => config('app.url') . '/oauth/authorize',
                        'token_url' => config('app.url') . '/oauth/token',
                        'scopes' => $scopes,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to regenerate credentials: ' . $e->getMessage(),
            ], 500);
        }
    }
}
