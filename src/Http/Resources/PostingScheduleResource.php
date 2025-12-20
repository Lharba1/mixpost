<?php

namespace Inovector\Mixpost\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostingScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'times' => PostingScheduleTimeResource::collection($this->whenLoaded('times')),
            'times_by_day' => $this->times_by_day,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
