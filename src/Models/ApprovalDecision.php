<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalDecision extends Model
{
    use HasFactory;

    public $table = 'mixpost_approval_decisions';

    public const DECISION_APPROVED = 'approved';
    public const DECISION_REJECTED = 'rejected';

    protected $fillable = [
        'approval_id',
        'user_id',
        'decision',
        'comment',
    ];

    /**
     * The approval request this decision belongs to
     */
    public function approval(): BelongsTo
    {
        return $this->belongsTo(PostApproval::class, 'approval_id');
    }

    /**
     * The user who made this decision
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if approved
     */
    public function isApproved(): bool
    {
        return $this->decision === self::DECISION_APPROVED;
    }

    /**
     * Check if rejected
     */
    public function isRejected(): bool
    {
        return $this->decision === self::DECISION_REJECTED;
    }

    /**
     * Get decision label
     */
    public function getDecisionLabelAttribute(): string
    {
        return match($this->decision) {
            self::DECISION_APPROVED => 'Approved',
            self::DECISION_REJECTED => 'Rejected',
            default => ucfirst($this->decision),
        };
    }
}
