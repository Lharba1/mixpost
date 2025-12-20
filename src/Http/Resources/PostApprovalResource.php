<?php

namespace Inovector\Mixpost\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostApprovalResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'post' => $this->whenLoaded('post', fn() => [
                'id' => $this->post->id,
                'uuid' => $this->post->uuid,
                'preview' => $this->post->versions->first()?->content[0]['body'] ?? 'No content',
                'status' => $this->post->status,
            ]),
            'workflow' => $this->whenLoaded('workflow', fn() => [
                'id' => $this->workflow->id,
                'name' => $this->workflow->name,
            ]),
            'requester' => $this->whenLoaded('requester', fn() => [
                'id' => $this->requester->id,
                'name' => $this->requester->name,
            ]),
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'notes' => $this->notes,
            'approvals_received' => $this->approvals_received,
            'approvals_required' => $this->approvals_required,
            'decisions' => $this->whenLoaded('decisions', fn() => 
                $this->decisions->map(fn($d) => [
                    'id' => $d->id,
                    'user' => [
                        'id' => $d->user->id,
                        'name' => $d->user->name,
                    ],
                    'decision' => $d->decision,
                    'comment' => $d->comment,
                    'created_at' => $d->created_at->diffForHumans(),
                ])
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'created_at_formatted' => $this->created_at->format('M j, Y g:i A'),
            'approved_at' => $this->approved_at?->diffForHumans(),
            'rejected_at' => $this->rejected_at?->diffForHumans(),
        ];
    }
}
