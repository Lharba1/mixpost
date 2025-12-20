<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inovector\Mixpost\Models\ApprovalWorkflow;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Models\PostApproval;
use Inovector\Mixpost\Models\User;
use Inovector\Mixpost\Http\Resources\PostApprovalResource;

class ApprovalController extends Controller
{
    /**
     * List pending approvals for current user
     */
    public function index(Request $request)
    {
        $pending = PostApproval::pendingFor(auth()->id());

        if ($request->wantsJson()) {
            return PostApprovalResource::collection($pending);
        }

        return Inertia::render('Approvals', [
            'pending_approvals' => PostApprovalResource::collection($pending),
            'workflows' => ApprovalWorkflow::where('is_active', true)->get(),
        ]);
    }

    /**
     * Request approval for a post
     */
    public function request(Request $request, Post $post)
    {
        $request->validate([
            'workflow_id' => 'nullable|exists:mixpost_approval_workflows,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $workflow = $request->workflow_id 
            ? ApprovalWorkflow::findOrFail($request->workflow_id)
            : ApprovalWorkflow::getDefault();

        if (!$workflow) {
            return back()->with('error', 'No approval workflow available.');
        }

        $approval = $workflow->createApprovalFor($post, auth()->id());
        
        if ($request->notes) {
            $approval->update(['notes' => $request->notes]);
        }

        $post->update([
            'requires_approval' => true,
            'approval_status' => 'pending',
        ]);

        return back()->with('success', 'Approval request submitted.');
    }

    /**
     * Approve a post
     */
    public function approve(Request $request, PostApproval $approval)
    {
        $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        if (!$approval->isPending()) {
            return back()->with('error', 'This approval is no longer pending.');
        }

        $approval->approve(auth()->id(), $request->comment);

        return back()->with('success', 'Post approved successfully.');
    }

    /**
     * Reject a post
     */
    public function reject(Request $request, PostApproval $approval)
    {
        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        if (!$approval->isPending()) {
            return back()->with('error', 'This approval is no longer pending.');
        }

        $approval->reject(auth()->id(), $request->comment);

        return back()->with('success', 'Post rejected.');
    }

    /**
     * Cancel an approval request
     */
    public function cancel(PostApproval $approval)
    {
        if ($approval->requested_by !== auth()->id()) {
            return back()->with('error', 'You can only cancel your own approval requests.');
        }

        $approval->cancel();

        return back()->with('success', 'Approval request cancelled.');
    }

    /**
     * List workflows
     */
    public function workflows(Request $request)
    {
        $workflows = ApprovalWorkflow::with('approvers')->get();

        if ($request->wantsJson()) {
            return response()->json($workflows);
        }

        return Inertia::render('ApprovalWorkflows', [
            'workflows' => $workflows,
            'users' => User::all(['id', 'name', 'email']),
        ]);
    }

    /**
     * Create a workflow
     */
    public function storeWorkflow(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'required_approvals' => 'required|integer|min:1',
            'require_all' => 'boolean',
            'approver_ids' => 'required|array|min:1',
            'approver_ids.*' => 'exists:users,id',
        ]);

        $workflow = ApprovalWorkflow::create([
            'name' => $request->name,
            'description' => $request->description,
            'required_approvals' => $request->required_approvals,
            'require_all' => $request->require_all ?? false,
            'is_active' => true,
        ]);

        // Attach approvers
        foreach ($request->approver_ids as $index => $userId) {
            $workflow->approvers()->attach($userId, ['order' => $index]);
        }

        return back()->with('success', 'Workflow created successfully.');
    }

    /**
     * Update a workflow
     */
    public function updateWorkflow(Request $request, ApprovalWorkflow $workflow)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'required_approvals' => 'required|integer|min:1',
            'require_all' => 'boolean',
            'is_active' => 'boolean',
            'approver_ids' => 'required|array|min:1',
            'approver_ids.*' => 'exists:users,id',
        ]);

        $workflow->update([
            'name' => $request->name,
            'description' => $request->description,
            'required_approvals' => $request->required_approvals,
            'require_all' => $request->require_all ?? false,
            'is_active' => $request->is_active ?? true,
        ]);

        // Sync approvers
        $workflow->approvers()->detach();
        foreach ($request->approver_ids as $index => $userId) {
            $workflow->approvers()->attach($userId, ['order' => $index]);
        }

        return back()->with('success', 'Workflow updated successfully.');
    }

    /**
     * Delete a workflow
     */
    public function deleteWorkflow(ApprovalWorkflow $workflow)
    {
        $workflow->delete();

        return back()->with('success', 'Workflow deleted.');
    }

    /**
     * Set workflow as default
     */
    public function setDefault(ApprovalWorkflow $workflow)
    {
        $workflow->makeDefault();

        return back()->with('success', 'Default workflow updated.');
    }
}
