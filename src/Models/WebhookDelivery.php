<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use HasFactory;

    public $table = 'mixpost_webhook_deliveries';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'response_code',
        'response_body',
        'status',
        'attempt',
        'delivered_at',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'delivered_at' => 'datetime',
    ];

    /**
     * The webhook this delivery belongs to
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Mark as success
     */
    public function markSuccess(int $responseCode, ?string $responseBody = null): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'response_code' => $responseCode,
            'response_body' => $responseBody,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markFailed(string $error, ?int $responseCode = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'response_code' => $responseCode,
            'error_message' => $error,
        ]);
    }

    /**
     * Increment attempt and mark pending for retry
     */
    public function retry(): void
    {
        $this->increment('attempt');
        $this->update(['status' => self::STATUS_PENDING]);
    }

    /**
     * Check if can retry
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED 
            && $this->attempt < $this->webhook->retry_count;
    }
}
