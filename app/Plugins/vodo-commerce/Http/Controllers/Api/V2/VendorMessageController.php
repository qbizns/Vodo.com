<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Resources\VendorMessageResource;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\VendorMessage;

class VendorMessageController
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * List all messages.
     */
    public function index(Request $request): JsonResponse
    {
        $query = VendorMessage::query();

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->forVendor($request->input('vendor_id'));
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->forCustomer($request->input('customer_id'));
        }

        // Filter by order
        if ($request->filled('order_id')) {
            $query->forOrder($request->input('order_id'));
        }

        // Root messages only (no replies)
        if ($request->boolean('root_only')) {
            $query->rootMessages();
        }

        // Replies only
        if ($request->boolean('replies_only')) {
            $query->replies();
        }

        // Filter unread
        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->byPriority($request->input('priority'));
        }

        // High priority only
        if ($request->boolean('high_priority_only')) {
            $query->highPriority();
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->byCategory($request->input('category'));
        }

        // Filter by sender type
        if ($request->filled('sender_type')) {
            $query->where('sender_type', $request->input('sender_type'));
        }

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->input('per_page', 15);
        $messages = $query->paginate($perPage);

        return $this->successResponse(
            VendorMessageResource::collection($messages),
            [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ]
        );
    }

    /**
     * Get a single message.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $message = VendorMessage::findOrFail($id);

        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $message->load($includes);
        }

        return $this->successResponse(
            new VendorMessageResource($message)
        );
    }

    /**
     * Create a new message.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vendor_id' => 'required|exists:commerce_vendors,id',
            'customer_id' => 'nullable|exists:commerce_customers,id',
            'order_id' => 'nullable|exists:commerce_orders,id',
            'parent_id' => 'nullable|exists:commerce_vendor_messages,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'attachments' => 'nullable|array',
            'sender_type' => 'required|in:customer,vendor,admin',
            'sender_id' => 'required|integer',
            'sender_name' => 'nullable|string|max:255',
            'sender_email' => 'nullable|email|max:255',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'category' => 'nullable|in:general,order,product,shipping,return,complaint,other',
            'meta' => 'nullable|array',
        ]);

        $message = VendorMessage::create($data);

        return $this->successResponse(
            new VendorMessageResource($message),
            null,
            'Message sent successfully',
            201
        );
    }

    /**
     * Update a message.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $message = VendorMessage::findOrFail($id);

        $data = $request->validate([
            'subject' => 'sometimes|required|string|max:255',
            'body' => 'sometimes|required|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'category' => 'nullable|in:general,order,product,shipping,return,complaint,other',
            'internal_notes' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        $message->update($data);

        return $this->successResponse(
            new VendorMessageResource($message),
            null,
            'Message updated successfully'
        );
    }

    /**
     * Delete a message.
     */
    public function destroy(int $id): JsonResponse
    {
        $message = VendorMessage::findOrFail($id);

        $message->delete();

        return $this->successResponse(
            null,
            null,
            'Message deleted successfully'
        );
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(int $id): JsonResponse
    {
        $message = VendorMessage::findOrFail($id);

        $message->markAsRead();

        return $this->successResponse(
            new VendorMessageResource($message),
            null,
            'Message marked as read'
        );
    }

    /**
     * Mark message as unread.
     */
    public function markAsUnread(int $id): JsonResponse
    {
        $message = VendorMessage::findOrFail($id);

        $message->markAsUnread();

        return $this->successResponse(
            new VendorMessageResource($message),
            null,
            'Message marked as unread'
        );
    }

    /**
     * Mark message as in progress.
     */
    public function markAsInProgress(int $id): JsonResponse
    {
        $message = VendorMessage::findOrFail($id);

        $message->markAsInProgress();

        return $this->successResponse(
            new VendorMessageResource($message),
            null,
            'Message marked as in progress'
        );
    }

    /**
     * Mark message as resolved.
     */
    public function markAsResolved(int $id): JsonResponse
    {
        $message = VendorMessage::findOrFail($id);

        $message->markAsResolved();

        return $this->successResponse(
            new VendorMessageResource($message),
            null,
            'Message marked as resolved'
        );
    }

    /**
     * Mark message as closed.
     */
    public function markAsClosed(int $id): JsonResponse
    {
        $message = VendorMessage::findOrFail($id);

        $message->markAsClosed();

        return $this->successResponse(
            new VendorMessageResource($message),
            null,
            'Message marked as closed'
        );
    }

    /**
     * Reopen a message.
     */
    public function reopen(int $id): JsonResponse
    {
        $message = VendorMessage::findOrFail($id);

        $message->reopen();

        return $this->successResponse(
            new VendorMessageResource($message),
            null,
            'Message reopened successfully'
        );
    }

    /**
     * Set message priority.
     */
    public function setPriority(Request $request, int $id): JsonResponse
    {
        $message = VendorMessage::findOrFail($id);

        $data = $request->validate([
            'priority' => 'required|in:low,normal,high,urgent',
        ]);

        $message->setPriority($data['priority']);

        return $this->successResponse(
            new VendorMessageResource($message),
            null,
            'Message priority updated'
        );
    }

    /**
     * Add attachment to message.
     */
    public function addAttachment(Request $request, int $id): JsonResponse
    {
        $message = VendorMessage::findOrFail($id);

        $data = $request->validate([
            'filename' => 'required|string|max:255',
            'url' => 'required|string|max:500',
        ]);

        $message->addAttachment($data['filename'], $data['url']);

        return $this->successResponse(
            new VendorMessageResource($message),
            null,
            'Attachment added successfully'
        );
    }

    /**
     * Add internal note.
     */
    public function addInternalNote(Request $request, int $id): JsonResponse
    {
        $message = VendorMessage::findOrFail($id);

        $data = $request->validate([
            'note' => 'required|string',
        ]);

        $message->addInternalNote($data['note']);

        return $this->successResponse(
            new VendorMessageResource($message),
            null,
            'Internal note added successfully'
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
