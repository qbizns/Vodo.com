<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Activity - Scheduled activities/tasks attached to records.
 * 
 * Like Odoo's activity scheduling system for follow-ups, calls, etc.
 */
class Activity extends Model
{
    protected $fillable = [
        'activity_type_id',
        'subject',
        'note',
        'due_date',
        'activityable_type',
        'activityable_id',
        'assigned_to',
        'created_by',
        'completed_at',
        'completed_by',
        'completed_note',
        'is_automated',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'is_automated' => 'boolean',
    ];

    /**
     * Get the activity type.
     */
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    /**
     * Get the record this activity is for.
     */
    public function activityable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the assigned user.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who completed the activity.
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Check if activity is completed.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Check if activity is overdue.
     */
    public function isOverdue(): bool
    {
        return !$this->isCompleted() && $this->due_date < now()->startOfDay();
    }

    /**
     * Check if activity is due today.
     */
    public function isDueToday(): bool
    {
        return !$this->isCompleted() && $this->due_date->isToday();
    }

    /**
     * Scope for pending activities.
     */
    public function scopePending($query)
    {
        return $query->whereNull('completed_at');
    }

    /**
     * Scope for completed activities.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Scope for overdue activities.
     */
    public function scopeOverdue($query)
    {
        return $query->pending()->where('due_date', '<', now()->startOfDay());
    }

    /**
     * Scope for today's activities.
     */
    public function scopeDueToday($query)
    {
        return $query->pending()->whereDate('due_date', now()->toDateString());
    }

    /**
     * Scope for assigned to user.
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Mark as completed.
     */
    public function markCompleted(?string $note = null, ?int $completedBy = null): void
    {
        $this->update([
            'completed_at' => now(),
            'completed_by' => $completedBy ?? auth()->id(),
            'completed_note' => $note,
        ]);
    }
}
