<?php

namespace Inovector\Mixpost\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostActivityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'action' => $this->action,
            'action_label' => $this->action_label,
            'action_icon' => $this->action_icon,
            'action_color' => $this->action_color,
            'description' => $this->description,
            'data' => $this->data,
            'changes' => $this->changes,
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'time_ago' => $this->time_ago,
            'created_at' => $this->created_at->toIso8601String(),
            'created_at_formatted' => $this->created_at->format('M j, Y g:i A'),
        ];
    }
}
