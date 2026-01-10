<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_vendor_messages';

    protected $fillable = [
        'vendor_id',
        'customer_id',
        'order_id',
        'parent_id',
        'subject',
        'body',
        'attachments',
        'sender_type',
        'sender_id',
        'sender_name',
        'sender_email',
        'is_read',
        'read_at',
        'priority',
        'status',
        'category',
        'internal_notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(VendorMessage::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(VendorMessage::class, 'parent_id');
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopeForVendor(Builder $query, int $vendorId): void
    {
        $query->where('vendor_id', $vendorId);
    }

    public function scopeForCustomer(Builder $query, int $customerId): void
    {
        $query->where('customer_id', $customerId);
    }

    public function scopeForOrder(Builder $query, int $orderId): void
    {
        $query->where('order_id', $orderId);
    }

    public function scopeRootMessages(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    public function scopeReplies(Builder $query): void
    {
        $query->whereNotNull('parent_id');
    }

    public function scopeUnread(Builder $query): void
    {
        $query->where('is_read', false);
    }

    public function scopeRead(Builder $query): void
    {
        $query->where('is_read', true);
    }

    public function scopeOpen(Builder $query): void
    {
        $query->where('status', 'open');
    }

    public function scopeInProgress(Builder $query): void
    {
        $query->where('status', 'in_progress');
    }

    public function scopeResolved(Builder $query): void
    {
        $query->where('status', 'resolved');
    }

    public function scopeClosed(Builder $query): void
    {
        $query->where('status', 'closed');
    }

    public function scopeByPriority(Builder $query, string $priority): void
    {
        $query->where('priority', $priority);
    }

    public function scopeHighPriority(Builder $query): void
    {
        $query->whereIn('priority', ['high', 'urgent']);
    }

    public function scopeByCategory(Builder $query, string $category): void
    {
        $query->where('category', $category);
    }

    public function scopeFromSender(Builder $query, string $senderType, int $senderId): void
    {
        $query->where('sender_type', $senderType)
            ->where('sender_id', $senderId);
    }

    public function scopeFromCustomers(Builder $query): void
    {
        $query->where('sender_type', 'customer');
    }

    public function scopeFromVendors(Builder $query): void
    {
        $query->where('sender_type', 'vendor');
    }

    public function scopeFromAdmins(Builder $query): void
    {
        $query->where('sender_type', 'admin');
    }

    // =========================================================================
    // BUSINESS LOGIC METHODS
    // =========================================================================

    public function markAsRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsUnread(): bool
    {
        return $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function markAsInProgress(): bool
    {
        return $this->update(['status' => 'in_progress']);
    }

    public function markAsResolved(): bool
    {
        return $this->update(['status' => 'resolved']);
    }

    public function markAsClosed(): bool
    {
        return $this->update(['status' => 'closed']);
    }

    public function reopen(): bool
    {
        return $this->update(['status' => 'open']);
    }

    public function setPriority(string $priority): bool
    {
        return $this->update(['priority' => $priority]);
    }

    public function addAttachment(string $filename, string $url): void
    {
        $attachments = $this->attachments ?? [];
        $attachments[] = [
            'filename' => $filename,
            'url' => $url,
            'uploaded_at' => now()->toDateTimeString(),
        ];

        $this->update(['attachments' => $attachments]);
    }

    public function removeAttachment(int $index): void
    {
        $attachments = $this->attachments ?? [];

        if (isset($attachments[$index])) {
            unset($attachments[$index]);
            $this->update(['attachments' => array_values($attachments)]);
        }
    }

    public function addInternalNote(string $note): void
    {
        $currentNotes = $this->internal_notes ?? '';
        $timestamp = now()->toDateTimeString();
        $newNote = "[{$timestamp}] {$note}";

        $updatedNotes = $currentNotes
            ? $currentNotes . "\n" . $newNote
            : $newNote;

        $this->update(['internal_notes' => $updatedNotes]);
    }

    public function isRead(): bool
    {
        return $this->is_read === true;
    }

    public function isUnread(): bool
    {
        return $this->is_read === false;
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isHighPriority(): bool
    {
        return in_array($this->priority, ['high', 'urgent']);
    }

    public function isRootMessage(): bool
    {
        return is_null($this->parent_id);
    }

    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    public function getThread(): array
    {
        if ($this->isReply()) {
            $root = $this->parent;
            return [$root, ...$root->replies];
        }

        return [$this, ...$this->replies];
    }
}
