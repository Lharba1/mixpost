<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Branding extends Model
{
    use HasFactory;

    public $table = 'mixpost_branding';

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    // Cache key
    protected const CACHE_KEY = 'mixpost_branding';
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Get a branding value
     */
    public static function get(string $key, $default = null)
    {
        $all = static::all();
        $setting = $all->firstWhere('key', $key);
        
        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a branding value
     */
    public static function set(string $key, $value, string $type = 'text'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );

        static::clearCache();
    }

    /**
     * Get all branding settings
     */
    public static function all($columns = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember(static::CACHE_KEY, static::CACHE_TTL, function () {
            return parent::all();
        });
    }

    /**
     * Get all branding as array
     */
    public static function allAsArray(): array
    {
        return static::all()
            ->mapWithKeys(fn($item) => [
                $item->key => static::castValue($item->value, $item->type)
            ])
            ->toArray();
    }

    /**
     * Cast value based on type
     */
    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            default => $value,
        };
    }

    /**
     * Clear the branding cache
     */
    public static function clearCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    /**
     * Get app name
     */
    public static function appName(): string
    {
        return static::get('app_name', 'Mixpost');
    }

    /**
     * Get logo URL (light mode)
     */
    public static function logoLight(): ?string
    {
        return static::get('logo_light');
    }

    /**
     * Get logo URL (dark mode)
     */
    public static function logoDark(): ?string
    {
        return static::get('logo_dark');
    }

    /**
     * Get favicon URL
     */
    public static function favicon(): ?string
    {
        return static::get('favicon');
    }

    /**
     * Get primary color
     */
    public static function primaryColor(): string
    {
        return static::get('primary_color', '#6366f1');
    }

    /**
     * Should hide powered by
     */
    public static function hidePoweredBy(): bool
    {
        return static::get('hide_powered_by', false);
    }

    /**
     * Get custom CSS
     */
    public static function customCss(): ?string
    {
        return static::get('custom_css');
    }

    /**
     * Get branding for frontend
     */
    public static function forFrontend(): array
    {
        return [
            'app_name' => static::appName(),
            'logo_light' => static::logoLight(),
            'logo_dark' => static::logoDark(),
            'favicon' => static::favicon(),
            'primary_color' => static::primaryColor(),
            'secondary_color' => static::get('secondary_color', '#8b5cf6'),
            'footer_text' => static::get('footer_text'),
            'hide_powered_by' => static::hidePoweredBy(),
            'custom_css' => static::customCss(),
        ];
    }
}
