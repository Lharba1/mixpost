<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostThread extends Model
{
    use HasFactory;

    public $table = 'mixpost_post_threads';

    protected $fillable = [
        'post_id',
        'account_id',
        'order',
        'content',
        'first_comment',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the body text from content
     */
    public function getBodyAttribute(): string
    {
        return $this->content[0]['body'] ?? '';
    }

    /**
     * Get media IDs from content
     */
    public function getMediaIdsAttribute(): array
    {
        return $this->content[0]['media'] ?? [];
    }
}
