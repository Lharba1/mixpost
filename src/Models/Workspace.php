<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Workspace extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'mixpost_workspaces';

    // Roles
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';
    public const ROLE_VIEWER = 'viewer';

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'color',
        'owner_id',
        'is_default',
        'settings',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'settings' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workspace) {
            if (!$workspace->uuid) {
                $workspace->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The owner of the workspace
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Members of the workspace
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mixpost_workspace_members', 'workspace_id', 'user_id')
            ->withPivot('role', 'permissions', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Pending invitations
     */
    public function invites(): HasMany
    {
        return $this->hasMany(WorkspaceInvite::class);
    }

    /**
     * Accounts in this workspace
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Posts in this workspace
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Add a member to the workspace
     */
    public function addMember(User $user, string $role = self::ROLE_MEMBER): void
    {
        $this->members()->attach($user->id, [
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    /**
     * Remove a member from the workspace
     */
    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
    }

    /**
     * Update member role
     */
    public function updateMemberRole(User $user, string $role): void
    {
        $this->members()->updateExistingPivot($user->id, ['role' => $role]);
    }

    /**
     * Check if user is a member
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Get member role
     */
    public function getMemberRole(User $user): ?string
    {
        $member = $this->members()->where('user_id', $user->id)->first();
        return $member?->pivot?->role;
    }

    /**
     * Check if user is owner
     */
    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    /**
     * Check if user is admin or owner
     */
    public function isAdmin(User $user): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        $role = $this->getMemberRole($user);
        return $role === self::ROLE_ADMIN;
    }

    /**
     * Check if user can manage workspace
     */
    public function canManage(User $user): bool
    {
        return $this->isOwner($user) || $this->isAdmin($user);
    }

    /**
     * Create a new workspace for a user
     */
    public static function createForUser(User $user, string $name, ?string $description = null): self
    {
        $workspace = static::create([
            'name' => $name,
            'description' => $description,
            'owner_id' => $user->id,
        ]);

        $workspace->addMember($user, self::ROLE_OWNER);

        return $workspace;
    }

    /**
     * Get available roles
     */
    public static function roles(): array
    {
        return [
            self::ROLE_OWNER => 'Owner',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_MEMBER => 'Member',
            self::ROLE_VIEWER => 'Viewer',
        ];
    }
}
