<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WorkspaceInvite extends Model
{
    use HasFactory;

    public $table = 'mixpost_workspace_invites';

    protected $fillable = [
        'workspace_id',
        'email',
        'token',
        'role',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invite) {
            if (!$invite->token) {
                $invite->token = Str::random(64);
            }
            if (!$invite->expires_at) {
                $invite->expires_at = now()->addDays(7);
            }
        });
    }

    /**
     * The workspace this invite is for
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * The user who sent the invite
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if invite is valid
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isAccepted();
    }

    /**
     * Check if invite is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if invite is accepted
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Accept the invite
     */
    public function accept(User $user): void
    {
        if (!$this->isValid()) {
            throw new \Exception('This invite is no longer valid.');
        }

        $this->workspace->addMember($user, $this->role);
        $this->update(['accepted_at' => now()]);
    }

    /**
     * Find by token
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('token', $token)
            ->with('workspace')
            ->first();
    }

    /**
     * Create an invite
     */
    public static function createFor(Workspace $workspace, string $email, string $role = 'member', ?int $invitedBy = null): self
    {
        // Delete any existing invite for this email
        static::where('workspace_id', $workspace->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->delete();

        return static::create([
            'workspace_id' => $workspace->id,
            'email' => $email,
            'role' => $role,
            'invited_by' => $invitedBy ?? auth()->id(),
        ]);
    }
}
