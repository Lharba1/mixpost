<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class RecyclingPost extends Model
{
    use HasFactory;
    use HasUuid;

    public $table = 'mixpost_recycling_posts';

    protected $fillable = [
        'post_id',
        'workspace_id',
        'interval_type',
        'interval_value',
        'max_recycles',
        'recycle_count',
        'is_active',
        'last_recycled_at',
        'next_recycle_at',
    ];

    protected $casts = [
        'interval_value' => 'integer',
        'max_recycles' => 'integer',
        'recycle_count' => 'integer',
        'is_active' => 'boolean',
        'last_recycled_at' => 'datetime',
        'next_recycle_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope for active recycling posts due for republishing
     */
    public function scopeDueForRecycling($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('next_recycle_at')
                  ->orWhere('next_recycle_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_recycles')
                  ->orWhereColumn('recycle_count', '<', 'max_recycles');
            });
    }

    /**
     * Calculate and set the next recycle date
     */
    public function calculateNextRecycleAt(): void
    {
        $baseDate = $this->last_recycled_at ?? now();
        
        $nextDate = match ($this->interval_type) {
            'hours' => $baseDate->addHours($this->interval_value),
            'days' => $baseDate->addDays($this->interval_value),
            'weeks' => $baseDate->addWeeks($this->interval_value),
            'months' => $baseDate->addMonths($this->interval_value),
            default => $baseDate->addDays(7),
        };

        $this->next_recycle_at = $nextDate;
    }

    /**
     * Mark as recycled and update counters
     */
    public function markAsRecycled(): void
    {
        $this->recycle_count++;
        $this->last_recycled_at = now();
        $this->calculateNextRecycleAt();
        
        // Check if max recycles reached
        if ($this->max_recycles && $this->recycle_count >= $this->max_recycles) {
            $this->is_active = false;
        }
        
        $this->save();
    }

    /**
     * Check if more recycles are allowed
     */
    public function canRecycle(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->max_recycles === null) {
            return true;
        }

        return $this->recycle_count < $this->max_recycles;
    }

    /**
     * Human-readable interval description
     */
    public function getIntervalDescriptionAttribute(): string
    {
        $value = $this->interval_value;
        $type = $this->interval_type;
        
        if ($value === 1) {
            $type = rtrim($type, 's'); // Remove plural
        }

        return "Every {$value} {$type}";
    }
}
