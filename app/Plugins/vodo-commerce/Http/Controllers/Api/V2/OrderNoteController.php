<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Requests\AddOrderNoteRequest;
use VodoCommerce\Http\Resources\OrderNoteResource;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderNote;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\OrderNoteService;

class OrderNoteController extends Controller
{
    public function __construct(protected OrderNoteService $noteService)
    {
    }

    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    public function index(Request $request, int $orderId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $order = Order::where('store_id', $store->id)->findOrFail($orderId);

        $query = $order->notes()->orderBy('created_at', 'desc');

        if ($request->has('customer_visible')) {
            if ($request->boolean('customer_visible')) {
                $query->customerVisible();
            } else {
                $query->adminOnly();
            }
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $notes = $query->paginate($perPage);

        return $this->successResponse(
            OrderNoteResource::collection($notes),
            $this->paginationMeta($notes)
        );
    }

    public function store(AddOrderNoteRequest $request, int $orderId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $order = Order::where('store_id', $store->id)->findOrFail($orderId);

        $note = $this->noteService->addNote($order, $request->input('content'), [
            'is_customer_visible' => $request->boolean('is_customer_visible', false),
            'author_type' => 'admin',
            'author_id' => auth()->id(),
        ]);

        return $this->successResponse(
            new OrderNoteResource($note),
            null,
            'Note added successfully',
            201
        );
    }

    public function update(AddOrderNoteRequest $request, int $noteId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $note = OrderNote::where('store_id', $store->id)->findOrFail($noteId);

        $updatedNote = $this->noteService->updateNote($note, $request->validated());

        return $this->successResponse(
            new OrderNoteResource($updatedNote),
            null,
            'Note updated successfully'
        );
    }

    public function destroy(int $noteId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $note = OrderNote::where('store_id', $store->id)->findOrFail($noteId);

        $this->noteService->deleteNote($note);

        return $this->successResponse(
            null,
            null,
            'Note deleted successfully'
        );
    }
}
