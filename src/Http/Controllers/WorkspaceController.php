<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inovector\Mixpost\Models\Workspace;
use Inovector\Mixpost\Models\WorkspaceInvite;
use Inovector\Mixpost\Models\User;

class WorkspaceController extends Controller
{
    /**
     * List user's workspaces
     */
    public function index(Request $request)
    {
        $workspaces = auth()->user()->workspaces()
            ->withCount('members')
            ->withCount('accounts')
            ->get();

        if ($request->wantsJson()) {
            return response()->json($workspaces);
        }

        return Inertia::render('Workspaces', [
            'workspaces' => $workspaces,
            'current_workspace_id' => auth()->user()->current_workspace_id,
        ]);
    }

    /**
     * Create a new workspace
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:20',
        ]);

        $workspace = Workspace::createForUser(
            auth()->user(),
            $request->name,
            $request->description
        );

        if ($request->color) {
            $workspace->update(['color' => $request->color]);
        }

        return back()->with('success', 'Workspace created successfully.');
    }

    /**
     * Update a workspace
     */
    public function update(Request $request, Workspace $workspace)
    {
        if (!$workspace->canManage(auth()->user())) {
            abort(403, 'You cannot manage this workspace.');
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:20',
        ]);

        $workspace->update([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color ?? $workspace->color,
        ]);

        return back()->with('success', 'Workspace updated successfully.');
    }

    /**
     * Delete a workspace
     */
    public function destroy(Workspace $workspace)
    {
        if (!$workspace->isOwner(auth()->user())) {
            abort(403, 'Only the owner can delete the workspace.');
        }

        $workspace->delete();

        return back()->with('success', 'Workspace deleted.');
    }

    /**
     * Switch to a workspace
     */
    public function switch(Workspace $workspace)
    {
        if (!$workspace->hasMember(auth()->user())) {
            abort(403, 'You are not a member of this workspace.');
        }

        auth()->user()->update(['current_workspace_id' => $workspace->id]);

        return back()->with('success', "Switched to {$workspace->name}");
    }

    /**
     * List workspace members
     */
    public function members(Workspace $workspace)
    {
        if (!$workspace->hasMember(auth()->user())) {
            abort(403);
        }

        return response()->json([
            'members' => $workspace->members()->get()->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'role' => $m->pivot->role,
                'joined_at' => $m->pivot->joined_at?->diffForHumans(),
            ]),
            'pending_invites' => $workspace->invites()
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->get(),
        ]);
    }

    /**
     * Invite a user to the workspace
     */
    public function invite(Request $request, Workspace $workspace)
    {
        if (!$workspace->canManage(auth()->user())) {
            abort(403);
        }

        $request->validate([
            'email' => 'required|email',
            'role' => 'required|string|in:admin,member,viewer',
        ]);

        // Check if already a member
        $user = User::where('email', $request->email)->first();
        if ($user && $workspace->hasMember($user)) {
            return back()->with('error', 'This user is already a member.');
        }

        $invite = WorkspaceInvite::createFor(
            $workspace,
            $request->email,
            $request->role
        );

        // In production, send email here
        // Mail::to($request->email)->send(new WorkspaceInvitation($invite));

        return back()->with('success', 'Invitation sent.');
    }

    /**
     * Remove a member
     */
    public function removeMember(Request $request, Workspace $workspace)
    {
        if (!$workspace->canManage(auth()->user())) {
            abort(403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($workspace->isOwner($user)) {
            return back()->with('error', 'Cannot remove the owner.');
        }

        $workspace->removeMember($user);

        return back()->with('success', 'Member removed.');
    }

    /**
     * Update member role
     */
    public function updateRole(Request $request, Workspace $workspace)
    {
        if (!$workspace->canManage(auth()->user())) {
            abort(403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|in:admin,member,viewer',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($workspace->isOwner($user)) {
            return back()->with('error', 'Cannot change owner role.');
        }

        $workspace->updateMemberRole($user, $request->role);

        return back()->with('success', 'Role updated.');
    }

    /**
     * Accept an invitation
     */
    public function acceptInvite(string $token)
    {
        $invite = WorkspaceInvite::findByToken($token);

        if (!$invite || !$invite->isValid()) {
            return redirect()->route('mixpost.dashboard')
                ->with('error', 'Invalid or expired invitation.');
        }

        try {
            $invite->accept(auth()->user());
            auth()->user()->update(['current_workspace_id' => $invite->workspace_id]);

            return redirect()->route('mixpost.dashboard')
                ->with('success', "Welcome to {$invite->workspace->name}!");
        } catch (\Exception $e) {
            return redirect()->route('mixpost.dashboard')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Leave a workspace
     */
    public function leave(Workspace $workspace)
    {
        if ($workspace->isOwner(auth()->user())) {
            return back()->with('error', 'Owner cannot leave. Transfer ownership first.');
        }

        $workspace->removeMember(auth()->user());

        // Switch to another workspace
        $otherWorkspace = auth()->user()->workspaces()->first();
        auth()->user()->update([
            'current_workspace_id' => $otherWorkspace?->id,
        ]);

        return redirect()->route('mixpost.workspaces.index')
            ->with('success', 'You left the workspace.');
    }
}
