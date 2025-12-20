<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class PostTemplate extends Model
{
    use HasFactory;
    use HasUuid;

    public $table = 'mixpost_post_templates';

    protected $fillable = [
        'name',
        'description',
        'content',
        'category',
        'is_default',
    ];

    protected $casts = [
        'content' => 'array',
        'is_default' => 'boolean',
    ];

    /**
     * Scope to filter by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get all unique categories
     */
    public static function getCategories(): array
    {
        return static::whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->toArray();
    }

    /**
     * Get preview text from content
     */
    public function getPreviewTextAttribute(): string
    {
        $body = $this->content[0]['body'] ?? '';
        $text = strip_tags(html_entity_decode($body));
        
        return strlen($text) > 100 ? substr($text, 0, 100) . '...' : $text;
    }

    /**
     * Check if template has media
     */
    public function getHasMediaAttribute(): bool
    {
        return !empty($this->content[0]['media'] ?? []);
    }

    /**
     * Get media count
     */
    public function getMediaCountAttribute(): int
    {
        return count($this->content[0]['media'] ?? []);
    }
}
