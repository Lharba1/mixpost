<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends Model
{
    use HasFactory;

    public $table = 'mixpost_approval_workflows';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'is_default',
        'required_approvals',
        'require_all',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'require_all' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Approvers assigned to this workflow
     */
    public function approvers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mixpost_workflow_approvers', 'workflow_id', 'user_id')
            ->withPivot('order')
            ->orderBy('order')
            ->withTimestamps();
    }

    /**
     * Post approvals using this workflow
     */
    public function postApprovals(): HasMany
    {
        return $this->hasMany(PostApproval::class, 'workflow_id');
    }

    /**
     * Get the default workflow
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * Set this workflow as default
     */
    public function makeDefault(): void
    {
        static::where('is_default', true)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /**
     * Create an approval request for a post
     */
    public function createApprovalFor(Post $post, ?int $requestedById = null): PostApproval
    {
        return PostApproval::create([
            'post_id' => $post->id,
            'workflow_id' => $this->id,
            'requested_by' => $requestedById ?? auth()->id(),
            'status' => PostApproval::STATUS_PENDING,
            'approvals_required' => $this->require_all 
                ? $this->approvers()->count() 
                : $this->required_approvals,
        ]);
    }
}
