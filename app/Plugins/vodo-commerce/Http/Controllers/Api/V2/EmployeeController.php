<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Resources\EmployeeResource;
use VodoCommerce\Models\Employee;
use VodoCommerce\Models\Store;

class EmployeeController extends Controller
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = Employee::where('store_id', $store->id);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $employees = $query->latest()->paginate($perPage);

        return $this->successResponse(
            EmployeeResource::collection($employees),
            $this->getPaginationMeta($employees)
        );
    }

    public function show(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $employee = Employee::where('store_id', $store->id)->findOrFail($id);

        return $this->successResponse(new EmployeeResource($employee));
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

    protected function getPaginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
