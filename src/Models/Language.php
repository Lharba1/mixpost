<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    use HasFactory;

    public $table = 'mixpost_languages';

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'flag_emoji',
        'is_active',
        'is_default',
        'is_rtl',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_rtl' => 'boolean',
    ];

    /**
     * Get active languages ordered by sort order
     */
    public static function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get the default language
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Set this language as default
     */
    public function makeDefault(): void
    {
        static::where('is_default', true)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /**
     * Display label with flag
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->flag_emoji 
            ? "{$this->flag_emoji} {$this->native_name}"
            : $this->native_name;
    }

    /**
     * Translations in this language
     */
    public function translations(): HasMany
    {
        return $this->hasMany(PostTranslation::class, 'language_code', 'code');
    }
}
