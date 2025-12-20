<?php

namespace Inovector\Mixpost\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'post_id' => $this->post_id,
            'post' => new PostResource($this->whenLoaded('post')),
            'schedule_time' => new PostingScheduleTimeResource($this->whenLoaded('scheduleTime')),
            'scheduled_at' => $this->scheduled_at?->toDateTimeString(),
            'scheduled_at_formatted' => $this->scheduled_at?->format('M j, Y g:i A'),
            'scheduled_at_relative' => $this->scheduled_at?->diffForHumans(),
            'status' => $this->status,
            'position' => $this->position,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
