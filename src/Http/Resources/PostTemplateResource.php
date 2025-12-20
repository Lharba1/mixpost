<?php

namespace Inovector\Mixpost\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'content' => $this->content,
            'category' => $this->category,
            'is_default' => $this->is_default,
            'preview_text' => $this->preview_text,
            'has_media' => $this->has_media,
            'media_count' => $this->media_count,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
