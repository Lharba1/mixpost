<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostingScheduleTime extends Model
{
    use HasFactory;

    public $table = 'mixpost_posting_schedule_times';

    protected $fillable = [
        'schedule_id',
        'day_of_week',
        'time',
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Day names for display
     */
    public const DAY_NAMES = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(PostingSchedule::class, 'schedule_id');
    }

    /**
     * Get the day name
     */
    public function getDayNameAttribute(): string
    {
        return self::DAY_NAMES[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Get formatted time
     */
    public function getFormattedTimeAttribute(): string
    {
        return \Carbon\Carbon::parse($this->time)->format('g:i A');
    }
}
