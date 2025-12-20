<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostApproval extends Model
{
    use HasFactory;

    public $table = 'mixpost_post_approvals';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'post_id',
        'workflow_id',
        'requested_by',
        'status',
        'notes',
        'approvals_received',
        'approvals_required',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * The post being approved
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * The workflow used for this approval
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    /**
     * User who requested the approval
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Individual decisions made on this approval
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(ApprovalDecision::class, 'approval_id');
    }

    /**
     * Check if pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Record an approval decision
     */
    public function approve(int $userId, ?string $comment = null): ApprovalDecision
    {
        $decision = $this->decisions()->updateOrCreate(
            ['user_id' => $userId],
            [
                'decision' => ApprovalDecision::DECISION_APPROVED,
                'comment' => $comment,
            ]
        );

        $this->approvals_received = $this->decisions()
            ->where('decision', ApprovalDecision::DECISION_APPROVED)
            ->count();

        if ($this->approvals_received >= $this->approvals_required) {
            $this->status = self::STATUS_APPROVED;
            $this->approved_at = now();
            
            // Update post approval status
            $this->post->update(['approval_status' => 'approved']);
            
            // Log activity
            PostActivity::log($this->post, PostActivity::ACTION_APPROVED, 'Post approved');
        }

        $this->save();

        return $decision;
    }

    /**
     * Record a rejection decision
     */
    public function reject(int $userId, ?string $comment = null): ApprovalDecision
    {
        $decision = $this->decisions()->updateOrCreate(
            ['user_id' => $userId],
            [
                'decision' => ApprovalDecision::DECISION_REJECTED,
                'comment' => $comment,
            ]
        );

        $this->status = self::STATUS_REJECTED;
        $this->rejected_at = now();
        $this->save();

        // Update post approval status
        $this->post->update(['approval_status' => 'rejected']);

        // Log activity
        PostActivity::log($this->post, PostActivity::ACTION_REJECTED, 'Post rejected: ' . $comment);

        return $decision;
    }

    /**
     * Cancel this approval request
     */
    public function cancel(): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->save();

        $this->post->update(['approval_status' => null, 'requires_approval' => false]);
    }

    /**
     * Get pending approvals for a user
     */
    public static function pendingFor(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('status', self::STATUS_PENDING)
            ->whereHas('workflow.approvers', fn($q) => $q->where('user_id', $userId))
            ->whereDoesntHave('decisions', fn($q) => $q->where('user_id', $userId))
            ->with(['post', 'requester'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }
}
