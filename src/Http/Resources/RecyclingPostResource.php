<?php

namespace Inovector\Mixpost\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecyclingPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'post_id' => $this->post_id,
            'post' => $this->when($this->relationLoaded('post'), function () {
                return [
                    'id' => $this->post->id,
                    'uuid' => $this->post->uuid,
                    'excerpt' => $this->getPostExcerpt(),
                    'accounts' => $this->post->accounts->map(fn($a) => [
                        'id' => $a->id,
                        'name' => $a->name,
                        'provider' => $a->provider,
                        'image' => $a->image,
                    ]),
                ];
            }),
            'interval_type' => $this->interval_type,
            'interval_value' => $this->interval_value,
            'interval_description' => $this->interval_description,
            'max_recycles' => $this->max_recycles,
            'recycle_count' => $this->recycle_count,
            'is_active' => $this->is_active,
            'last_recycled_at' => $this->last_recycled_at?->toIso8601String(),
            'next_recycle_at' => $this->next_recycle_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    protected function getPostExcerpt(): string
    {
        $version = $this->post->versions->first();
        if (!$version) {
            return 'No content';
        }

        $content = $version->content[0]['body'] ?? '';
        $text = strip_tags($content);
        
        return \Illuminate\Support\Str::limit($text, 100);
    }
}
