<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class QueueItem extends Model
{
    use HasFactory;
    use HasUuid;

    public $table = 'mixpost_queue';

    protected $fillable = [
        'post_id',
        'schedule_time_id',
        'scheduled_at',
        'status',
        'position',
        'error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'position' => 'integer',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function scheduleTime(): BelongsTo
    {
        return $this->belongsTo(PostingScheduleTime::class, 'schedule_time_id');
    }

    /**
     * Scope for pending items
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for items ready to publish
     */
    public function scopeReadyToPublish($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope ordered by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('scheduled_at');
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Mark as published
     */
    public function markAsPublished(): void
    {
        $this->update(['status' => 'published']);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Requeue a failed item
     */
    public function requeue(): void
    {
        $this->update([
            'status' => 'pending',
            'error_message' => null,
        ]);
    }
}
