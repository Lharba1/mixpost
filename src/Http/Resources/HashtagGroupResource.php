<?php

namespace Inovector\Mixpost\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HashtagGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'hashtags' => $this->hashtags,
            'formatted_hashtags' => $this->formatted_hashtags,
            'hashtag_count' => $this->hashtag_count,
            'color' => $this->color,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
