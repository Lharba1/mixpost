<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class PostingSchedule extends Model
{
    use HasFactory;
    use HasUuid;

    public $table = 'mixpost_posting_schedules';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function times(): HasMany
    {
        return $this->hasMany(PostingScheduleTime::class, 'schedule_id');
    }

    /**
     * Get times grouped by day of week
     */
    public function getTimesByDayAttribute(): array
    {
        $grouped = [];
        
        foreach ($this->times as $time) {
            $day = $time->day_of_week;
            if (!isset($grouped[$day])) {
                $grouped[$day] = [];
            }
            $grouped[$day][] = $time;
        }
        
        return $grouped;
    }

    /**
     * Get next available slot for a given date
     */
    public function getNextAvailableSlot(?\Carbon\Carbon $after = null): ?PostingScheduleTime
    {
        $after = $after ?? now();
        $dayOfWeek = $after->dayOfWeek;
        $currentTime = $after->format('H:i:s');

        // First try to find a slot today after the current time
        $todaySlot = $this->times()
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->where('time', '>', $currentTime)
            ->orderBy('time')
            ->first();

        if ($todaySlot) {
            return $todaySlot;
        }

        // Find the next slot in the following days
        for ($i = 1; $i <= 7; $i++) {
            $targetDay = ($dayOfWeek + $i) % 7;
            
            $slot = $this->times()
                ->where('is_active', true)
                ->where('day_of_week', $targetDay)
                ->orderBy('time')
                ->first();

            if ($slot) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * Calculate the next publish datetime for a slot
     */
    public function getNextPublishDate(PostingScheduleTime $slot, ?\Carbon\Carbon $after = null): \Carbon\Carbon
    {
        $after = $after ?? now();
        $targetDay = $slot->day_of_week;
        $currentDay = $after->dayOfWeek;
        
        $daysUntilSlot = ($targetDay - $currentDay + 7) % 7;
        
        // If it's today but the time has passed, add 7 days
        if ($daysUntilSlot === 0 && $after->format('H:i:s') >= $slot->time) {
            $daysUntilSlot = 7;
        }
        
        return $after->copy()
            ->addDays($daysUntilSlot)
            ->setTimeFromTimeString($slot->time);
    }
}
