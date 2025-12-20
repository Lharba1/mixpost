<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inovector\Mixpost\Models\Webhook;
use Inovector\Mixpost\Models\WebhookDelivery;
use Inovector\Mixpost\Services\WebhookService;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    /**
     * List all webhooks
     */
    public function index(Request $request)
    {
        $webhooks = Webhook::withCount(['deliveries as success_count' => function ($q) {
            $q->where('status', 'success');
        }, 'deliveries as failed_count' => function ($q) {
            $q->where('status', 'failed');
        }])->get();

        if ($request->wantsJson()) {
            return response()->json($webhooks);
        }

        return Inertia::render('Webhooks', [
            'webhooks' => $webhooks,
            'available_events' => Webhook::availableEvents(),
        ]);
    }

    /**
     * Store a new webhook
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
            'secret' => 'nullable|string|max:255',
            'headers' => 'nullable|array',
            'timeout' => 'nullable|integer|min:5|max:120',
            'retry_count' => 'nullable|integer|min:0|max:10',
        ]);

        Webhook::create([
            'name' => $request->name,
            'url' => $request->url,
            'events' => $request->events,
            'secret' => $request->secret ?? Str::random(32),
            'headers' => $request->headers,
            'timeout' => $request->timeout ?? 30,
            'retry_count' => $request->retry_count ?? 3,
            'is_active' => true,
        ]);

        return back()->with('success', 'Webhook created successfully.');
    }

    /**
     * Update a webhook
     */
    public function update(Request $request, Webhook $webhook)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'is_active' => 'boolean',
            'secret' => 'nullable|string|max:255',
            'headers' => 'nullable|array',
            'timeout' => 'nullable|integer|min:5|max:120',
            'retry_count' => 'nullable|integer|min:0|max:10',
        ]);

        $webhook->update([
            'name' => $request->name,
            'url' => $request->url,
            'events' => $request->events,
            'is_active' => $request->is_active ?? true,
            'secret' => $request->secret ?? $webhook->secret,
            'headers' => $request->headers,
            'timeout' => $request->timeout ?? 30,
            'retry_count' => $request->retry_count ?? 3,
        ]);

        return back()->with('success', 'Webhook updated successfully.');
    }

    /**
     * Delete a webhook
     */
    public function destroy(Webhook $webhook)
    {
        $webhook->delete();

        return back()->with('success', 'Webhook deleted.');
    }

    /**
     * Toggle webhook active status
     */
    public function toggle(Webhook $webhook)
    {
        $webhook->update(['is_active' => !$webhook->is_active]);

        return back()->with('success', $webhook->is_active ? 'Webhook activated.' : 'Webhook deactivated.');
    }

    /**
     * View delivery history for a webhook
     */
    public function deliveries(Request $request, Webhook $webhook)
    {
        $deliveries = $webhook->deliveries()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json($deliveries);
    }

    /**
     * Retry a failed delivery
     */
    public function retry(WebhookDelivery $delivery, WebhookService $webhookService)
    {
        if (!$delivery->canRetry()) {
            return back()->with('error', 'Cannot retry this delivery.');
        }

        $webhookService->retry($delivery);

        return back()->with('success', 'Webhook delivery retried.');
    }

    /**
     * Test a webhook
     */
    public function test(Webhook $webhook, WebhookService $webhookService)
    {
        $delivery = $webhookService->send($webhook, 'test.ping', [
            'message' => 'This is a test webhook from Mixpost',
            'timestamp' => now()->toIso8601String(),
        ]);

        if ($delivery->status === WebhookDelivery::STATUS_SUCCESS) {
            return back()->with('success', 'Test webhook sent successfully!');
        }

        return back()->with('error', 'Webhook test failed: ' . $delivery->error_message);
    }

    /**
     * Regenerate webhook secret
     */
    public function regenerateSecret(Webhook $webhook)
    {
        $webhook->update(['secret' => Str::random(32)]);

        return back()->with('success', 'Webhook secret regenerated.');
    }
}
