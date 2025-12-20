<?php

namespace Inovector\Mixpost\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostingScheduleTimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'schedule_id' => $this->schedule_id,
            'day_of_week' => $this->day_of_week,
            'day_name' => $this->day_name,
            'time' => $this->time,
            'formatted_time' => $this->formatted_time,
            'is_active' => $this->is_active,
        ];
    }
}
