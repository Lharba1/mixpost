<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    use HasFactory;

    public $table = 'mixpost_api_tokens';

    // Available abilities/scopes
    public const ABILITY_READ_POSTS = 'posts:read';
    public const ABILITY_WRITE_POSTS = 'posts:write';
    public const ABILITY_READ_ACCOUNTS = 'accounts:read';
    public const ABILITY_WRITE_ACCOUNTS = 'accounts:write';
    public const ABILITY_READ_MEDIA = 'media:read';
    public const ABILITY_WRITE_MEDIA = 'media:write';
    public const ABILITY_READ_ANALYTICS = 'analytics:read';
    public const ABILITY_MANAGE_SETTINGS = 'settings:manage';

    protected $fillable = [
        'name',
        'token',
        'user_id',
        'abilities',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * User who created this token
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Request logs for this token
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ApiLog::class, 'token_id');
    }

    /**
     * Generate a new API token
     */
    public static function generate(string $name, ?int $userId = null, array $abilities = []): self
    {
        return static::create([
            'name' => $name,
            'token' => Str::random(64),
            'user_id' => $userId ?? auth()->id(),
            'abilities' => $abilities ?: static::allAbilities(),
            'is_active' => true,
        ]);
    }

    /**
     * Find token by raw token string
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('token', $token)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Check if token has ability
     */
    public function can(string $ability): bool
    {
        return in_array('*', $this->abilities ?? []) 
            || in_array($ability, $this->abilities ?? []);
    }

    /**
     * Check if token has any of the abilities
     */
    public function canAny(array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if ($this->can($ability)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Mark token as used
     */
    public function markUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Check if token is valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Revoke the token
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get all available abilities
     */
    public static function allAbilities(): array
    {
        return [
            self::ABILITY_READ_POSTS,
            self::ABILITY_WRITE_POSTS,
            self::ABILITY_READ_ACCOUNTS,
            self::ABILITY_WRITE_ACCOUNTS,
            self::ABILITY_READ_MEDIA,
            self::ABILITY_WRITE_MEDIA,
            self::ABILITY_READ_ANALYTICS,
            self::ABILITY_MANAGE_SETTINGS,
        ];
    }

    /**
     * Get ability labels for UI
     */
    public static function abilityLabels(): array
    {
        return [
            self::ABILITY_READ_POSTS => 'Read Posts',
            self::ABILITY_WRITE_POSTS => 'Create/Edit Posts',
            self::ABILITY_READ_ACCOUNTS => 'Read Accounts',
            self::ABILITY_WRITE_ACCOUNTS => 'Manage Accounts',
            self::ABILITY_READ_MEDIA => 'Read Media',
            self::ABILITY_WRITE_MEDIA => 'Upload Media',
            self::ABILITY_READ_ANALYTICS => 'Read Analytics',
            self::ABILITY_MANAGE_SETTINGS => 'Manage Settings',
        ];
    }

    /**
     * Get masked token for display
     */
    public function getMaskedTokenAttribute(): string
    {
        return substr($this->token, 0, 8) . '...' . substr($this->token, -4);
    }
}
