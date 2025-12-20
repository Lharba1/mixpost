<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostTranslation extends Model
{
    use HasFactory;

    public $table = 'mixpost_post_translations';

    protected $fillable = [
        'post_id',
        'language_code',
        'content',
        'is_auto_translated',
        'translation_provider',
    ];

    protected $casts = [
        'content' => 'array',
        'is_auto_translated' => 'boolean',
    ];

    /**
     * The post this translation belongs to
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * The language of this translation
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_code', 'code');
    }

    /**
     * Get the text content for preview
     */
    public function getPreviewTextAttribute(): string
    {
        $firstVersion = $this->content[0] ?? [];
        return substr($firstVersion['body'] ?? '', 0, 100);
    }

    /**
     * Create or update translation for a post
     */
    public static function setTranslation(
        int $postId,
        string $languageCode,
        array $content,
        bool $isAutoTranslated = false,
        ?string $provider = null
    ): self {
        return static::updateOrCreate(
            ['post_id' => $postId, 'language_code' => $languageCode],
            [
                'content' => $content,
                'is_auto_translated' => $isAutoTranslated,
                'translation_provider' => $provider,
            ]
        );
    }

    /**
     * Get translation for a post in a specific language
     */
    public static function getTranslation(int $postId, string $languageCode): ?self
    {
        return static::where('post_id', $postId)
            ->where('language_code', $languageCode)
            ->first();
    }

    /**
     * Get all translations for a post
     */
    public static function forPost(int $postId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('post_id', $postId)
            ->with('language')
            ->get();
    }
}
