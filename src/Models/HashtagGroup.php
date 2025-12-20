<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class HashtagGroup extends Model
{
    use HasFactory;
    use HasUuid;

    public $table = 'mixpost_hashtag_groups';

    protected $fillable = [
        'name',
        'hashtags',
        'color',
    ];

    protected $casts = [
        'hashtags' => 'array',
    ];

    /**
     * Get hashtags as a formatted string
     */
    public function getFormattedHashtagsAttribute(): string
    {
        return collect($this->hashtags)
            ->map(function ($tag) {
                // Ensure each hashtag starts with #
                return str_starts_with($tag, '#') ? $tag : '#' . $tag;
            })
            ->implode(' ');
    }

    /**
     * Get the count of hashtags in this group
     */
    public function getHashtagCountAttribute(): int
    {
        return count($this->hashtags ?? []);
    }

    /**
     * Set hashtags from a string (converts to array)
     */
    public function setHashtagsFromString(string $hashtagsString): void
    {
        $hashtags = preg_split('/[\s,]+/', $hashtagsString);
        
        $this->hashtags = collect($hashtags)
            ->map(function ($tag) {
                $tag = trim($tag);
                // Remove # prefix for storage
                return ltrim($tag, '#');
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Search hashtag groups by name or hashtag content
     */
    public static function search(string $query)
    {
        return static::where('name', 'like', '%' . $query . '%')
            ->orWhereJsonContains('hashtags', $query);
    }
}
