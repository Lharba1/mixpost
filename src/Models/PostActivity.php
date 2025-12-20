<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostActivity extends Model
{
    use HasFactory;

    public $table = 'mixpost_post_activities';

    protected $fillable = [
        'post_id',
        'user_id',
        'action',
        'description',
        'data',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'data' => 'array',
        'changes' => 'array',
    ];

    // Action types
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_SCHEDULED = 'scheduled';
    public const ACTION_PUBLISHED = 'published';
    public const ACTION_FAILED = 'failed';
    public const ACTION_RESTORED = 'restored';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_VERSION_ADDED = 'version_added';
    public const ACTION_MEDIA_ADDED = 'media_added';
    public const ACTION_MEDIA_REMOVED = 'media_removed';
    public const ACTION_ACCOUNT_ADDED = 'account_added';
    public const ACTION_ACCOUNT_REMOVED = 'account_removed';
    public const ACTION_QUEUED = 'queued';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';

    /**
     * Get the post for this activity
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user who performed this activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an activity for a post
     */
    public static function log(
        Post $post,
        string $action,
        ?string $description = null,
        ?array $data = null,
        ?array $changes = null
    ): self {
        return static::create([
            'post_id' => $post->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'data' => $data,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get action label for display
     */
    public function getActionLabelAttribute(): string
    {
        $labels = [
            self::ACTION_CREATED => 'Created',
            self::ACTION_UPDATED => 'Updated',
            self::ACTION_SCHEDULED => 'Scheduled',
            self::ACTION_PUBLISHED => 'Published',
            self::ACTION_FAILED => 'Failed',
            self::ACTION_RESTORED => 'Restored',
            self::ACTION_DELETED => 'Deleted',
            self::ACTION_VERSION_ADDED => 'Version Added',
            self::ACTION_MEDIA_ADDED => 'Media Added',
            self::ACTION_MEDIA_REMOVED => 'Media Removed',
            self::ACTION_ACCOUNT_ADDED => 'Account Added',
            self::ACTION_ACCOUNT_REMOVED => 'Account Removed',
            self::ACTION_QUEUED => 'Queued',
            self::ACTION_APPROVED => 'Approved',
            self::ACTION_REJECTED => 'Rejected',
        ];

        return $labels[$this->action] ?? ucfirst($this->action);
    }

    /**
     * Get action icon for display
     */
    public function getActionIconAttribute(): string
    {
        $icons = [
            self::ACTION_CREATED => 'plus',
            self::ACTION_UPDATED => 'pencil',
            self::ACTION_SCHEDULED => 'clock',
            self::ACTION_PUBLISHED => 'check',
            self::ACTION_FAILED => 'x',
            self::ACTION_RESTORED => 'arrow-path',
            self::ACTION_DELETED => 'trash',
            self::ACTION_APPROVED => 'check-circle',
            self::ACTION_REJECTED => 'x-circle',
        ];

        return $icons[$this->action] ?? 'document';
    }

    /**
     * Get action color for display
     */
    public function getActionColorAttribute(): string
    {
        $colors = [
            self::ACTION_CREATED => 'blue',
            self::ACTION_UPDATED => 'yellow',
            self::ACTION_SCHEDULED => 'purple',
            self::ACTION_PUBLISHED => 'green',
            self::ACTION_FAILED => 'red',
            self::ACTION_RESTORED => 'blue',
            self::ACTION_DELETED => 'red',
            self::ACTION_APPROVED => 'green',
            self::ACTION_REJECTED => 'red',
        ];

        return $colors[$this->action] ?? 'gray';
    }

    /**
     * Get formatted time ago
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get activities for a post
     */
    public static function forPost(int $postId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('post_id', $postId)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
