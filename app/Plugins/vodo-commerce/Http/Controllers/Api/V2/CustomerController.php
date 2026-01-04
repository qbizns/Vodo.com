<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Requests\BanCustomerRequest;
use VodoCommerce\Http\Requests\ImportCustomersRequest;
use VodoCommerce\Http\Resources\CustomerResource;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Store;

class CustomerController extends Controller
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    public function ban(BanCustomerRequest $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();
        $customer = Customer::where('store_id', $store->id)->findOrFail($id);

        $customer->ban($request->input('reason'));

        do_action('commerce.customer.banned', $customer);

        return $this->successResponse(
            new CustomerResource($customer),
            null,
            'Customer banned successfully'
        );
    }

    public function unban(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();
        $customer = Customer::where('store_id', $store->id)->findOrFail($id);

        $customer->unban();

        do_action('commerce.customer.unbanned', $customer);

        return $this->successResponse(
            new CustomerResource($customer),
            null,
            'Customer unbanned successfully'
        );
    }

    public function import(ImportCustomersRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore();
        $customersData = $request->input('customers');

        $imported = [];
        $failed = [];

        foreach ($customersData as $index => $customerData) {
            try {
                $customer = Customer::firstOrCreate(
                    [
                        'store_id' => $store->id,
                        'email' => $customerData['email'],
                    ],
                    array_merge($customerData, ['store_id' => $store->id])
                );

                $imported[] = $customer->id;

                do_action('commerce.customer.imported', $customer);
            } catch (\Exception $e) {
                $failed[] = [
                    'index' => $index,
                    'email' => $customerData['email'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $this->successResponse(
            [
                'imported_count' => count($imported),
                'failed_count' => count($failed),
                'imported_ids' => $imported,
                'failed' => $failed,
            ],
            null,
            count($imported) . ' customers imported successfully',
            201
        );
    }

    protected function successResponse(mixed $data = null, ?array $pagination = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'status' => $status,
            'success' => true,
            'data' => $data,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($pagination) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $status);
    }
}
