<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webhook extends Model
{
    use HasFactory;

    public $table = 'mixpost_webhooks';

    // Supported events
    public const EVENT_POST_CREATED = 'post.created';
    public const EVENT_POST_UPDATED = 'post.updated';
    public const EVENT_POST_SCHEDULED = 'post.scheduled';
    public const EVENT_POST_PUBLISHED = 'post.published';
    public const EVENT_POST_FAILED = 'post.failed';
    public const EVENT_POST_DELETED = 'post.deleted';
    public const EVENT_ACCOUNT_ADDED = 'account.added';
    public const EVENT_ACCOUNT_REMOVED = 'account.removed';
    public const EVENT_APPROVAL_REQUESTED = 'approval.requested';
    public const EVENT_APPROVAL_APPROVED = 'approval.approved';
    public const EVENT_APPROVAL_REJECTED = 'approval.rejected';

    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'headers',
        'timeout',
        'retry_count',
    ];

    protected $casts = [
        'events' => 'array',
        'headers' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Delivery logs for this webhook
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Get active webhooks for an event
     */
    public static function forEvent(string $event): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();
    }

    /**
     * Check if webhook is subscribed to event
     */
    public function subscribedTo(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    /**
     * Get all available events
     */
    public static function availableEvents(): array
    {
        return [
            self::EVENT_POST_CREATED => 'Post Created',
            self::EVENT_POST_UPDATED => 'Post Updated',
            self::EVENT_POST_SCHEDULED => 'Post Scheduled',
            self::EVENT_POST_PUBLISHED => 'Post Published',
            self::EVENT_POST_FAILED => 'Post Failed',
            self::EVENT_POST_DELETED => 'Post Deleted',
            self::EVENT_ACCOUNT_ADDED => 'Account Added',
            self::EVENT_ACCOUNT_REMOVED => 'Account Removed',
            self::EVENT_APPROVAL_REQUESTED => 'Approval Requested',
            self::EVENT_APPROVAL_APPROVED => 'Approval Approved',
            self::EVENT_APPROVAL_REJECTED => 'Approval Rejected',
        ];
    }

    /**
     * Generate a signature for the payload
     */
    public function signPayload(string $payload): ?string
    {
        if (!$this->secret) {
            return null;
        }

        return hash_hmac('sha256', $payload, $this->secret);
    }
}
