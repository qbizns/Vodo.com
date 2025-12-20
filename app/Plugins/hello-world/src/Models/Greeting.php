<?php

namespace HelloWorld\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Greeting extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'hello_world_greetings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'message',
        'author',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'short_message',
        'formatted_date',
    ];

    // ==================== Scopes ====================

    /**
     * Scope a query to search greetings.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('message', 'like', "%{$term}%")
              ->orWhere('author', 'like', "%{$term}%");
        });
    }

    /**
     * Scope a query to filter by author.
     */
    public function scopeByAuthor(Builder $query, string $author): Builder
    {
        return $query->where('author', $author);
    }

    /**
     * Scope a query to get recent greetings.
     */
    public function scopeRecent(Builder $query, int $limit = 5): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope a query to get today's greetings.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope a query to get greetings from this week.
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    /**
     * Scope a query to get greetings from this month.
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }

    /**
     * Scope a query to get greetings by date range.
     */
    public function scopeBetweenDates(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // ==================== Accessors ====================

    /**
     * Get a shortened version of the message.
     */
    public function getShortMessageAttribute(): string
    {
        return \Illuminate\Support\Str::limit($this->message, 50);
    }

    /**
     * Get the formatted created date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at?->format('M j, Y g:i A') ?? '';
    }

    /**
     * Get the relative time (e.g., "2 hours ago").
     */
    public function getRelativeTimeAttribute(): string
    {
        return $this->created_at?->diffForHumans() ?? '';
    }

    // ==================== Methods ====================

    /**
     * Check if the greeting is from today.
     */
    public function isFromToday(): bool
    {
        return $this->created_at?->isToday() ?? false;
    }

    /**
     * Check if the greeting is from a specific author.
     */
    public function isFromAuthor(string $author): bool
    {
        return strtolower($this->author) === strtolower($author);
    }

    /**
     * Get the message as HTML (escaping dangerous content).
     */
    public function getHtmlMessage(): string
    {
        return nl2br(e($this->message));
    }
}
