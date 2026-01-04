<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Database\Eloquent\Collection;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderNote;

class OrderNoteService
{
    /**
     * Add a note to an order.
     */
    public function addNote(Order $order, string $content, array $options = []): OrderNote
    {
        $note = $order->notes()->create([
            'store_id' => $order->store_id,
            'content' => $content,
            'is_customer_visible' => $options['is_customer_visible'] ?? false,
            'author_type' => $options['author_type'] ?? 'admin',
            'author_id' => $options['author_id'] ?? null,
        ]);

        do_action('commerce.order.note_added', $order, $note);

        return $note;
    }

    /**
     * Add a system note to an order.
     */
    public function addSystemNote(Order $order, string $content): OrderNote
    {
        return $this->addNote($order, $content, [
            'author_type' => 'system',
            'is_customer_visible' => false,
        ]);
    }

    /**
     * Get customer-visible notes for an order.
     */
    public function getCustomerVisibleNotes(Order $order): Collection
    {
        return $order->notes()
            ->customerVisible()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all notes for an order.
     */
    public function getAllNotes(Order $order): Collection
    {
        return $order->notes()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get notes by author type.
     */
    public function getNotesByAuthorType(Order $order, string $authorType): Collection
    {
        return $order->notes()
            ->byAuthorType($authorType)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Update a note.
     */
    public function updateNote(OrderNote $note, array $data): OrderNote
    {
        $note->update([
            'content' => $data['content'] ?? $note->content,
            'is_customer_visible' => $data['is_customer_visible'] ?? $note->is_customer_visible,
        ]);

        do_action('commerce.order.note_updated', $note);

        return $note->fresh();
    }

    /**
     * Delete a note.
     */
    public function deleteNote(OrderNote $note): bool
    {
        do_action('commerce.order.note_deleted', $note);

        return $note->delete();
    }
}
