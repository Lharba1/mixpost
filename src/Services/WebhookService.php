<?php

namespace Inovector\Mixpost\Services;

use Inovector\Mixpost\Models\Webhook;
use Inovector\Mixpost\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Dispatch an event to all subscribed webhooks
     */
    public function dispatch(string $event, array $data): void
    {
        $webhooks = Webhook::forEvent($event);

        foreach ($webhooks as $webhook) {
            $this->send($webhook, $event, $data);
        }
    }

    /**
     * Send webhook request
     */
    public function send(Webhook $webhook, string $event, array $data): WebhookDelivery
    {
        $payload = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ];

        $payloadJson = json_encode($payload);

        // Create delivery record
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event' => $event,
            'payload' => $payload,
            'status' => WebhookDelivery::STATUS_PENDING,
        ]);

        try {
            // Build headers
            $headers = $webhook->headers ?? [];
            $headers['Content-Type'] = 'application/json';
            $headers['User-Agent'] = 'Mixpost-Webhook/1.0';
            $headers['X-Mixpost-Event'] = $event;
            $headers['X-Mixpost-Delivery'] = (string) $delivery->id;

            // Add signature if secret is set
            if ($signature = $webhook->signPayload($payloadJson)) {
                $headers['X-Mixpost-Signature'] = $signature;
            }

            // Send request
            $response = Http::withHeaders($headers)
                ->timeout($webhook->timeout)
                ->post($webhook->url, $payload);

            if ($response->successful()) {
                $delivery->markSuccess($response->status(), $response->body());
            } else {
                $delivery->markFailed(
                    "HTTP Error: {$response->status()}",
                    $response->status()
                );
            }
        } catch (\Exception $e) {
            $delivery->markFailed($e->getMessage());
            Log::error('Webhook delivery failed', [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }

        return $delivery;
    }

    /**
     * Retry a failed delivery
     */
    public function retry(WebhookDelivery $delivery): WebhookDelivery
    {
        if (!$delivery->canRetry()) {
            return $delivery;
        }

        $delivery->retry();

        return $this->send(
            $delivery->webhook,
            $delivery->event,
            $delivery->payload['data'] ?? []
        );
    }

    /**
     * Dispatch post created event
     */
    public function postCreated($post): void
    {
        $this->dispatch(Webhook::EVENT_POST_CREATED, [
            'post_id' => $post->id,
            'uuid' => $post->uuid,
            'status' => $post->status,
        ]);
    }

    /**
     * Dispatch post published event
     */
    public function postPublished($post): void
    {
        $this->dispatch(Webhook::EVENT_POST_PUBLISHED, [
            'post_id' => $post->id,
            'uuid' => $post->uuid,
            'published_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Dispatch post failed event
     */
    public function postFailed($post, string $error): void
    {
        $this->dispatch(Webhook::EVENT_POST_FAILED, [
            'post_id' => $post->id,
            'uuid' => $post->uuid,
            'error' => $error,
        ]);
    }

    /**
     * Dispatch post scheduled event
     */
    public function postScheduled($post): void
    {
        $this->dispatch(Webhook::EVENT_POST_SCHEDULED, [
            'post_id' => $post->id,
            'uuid' => $post->uuid,
            'scheduled_at' => $post->scheduled_at?->toIso8601String(),
        ]);
    }

    /**
     * Dispatch approval events
     */
    public function approvalRequested($approval): void
    {
        $this->dispatch(Webhook::EVENT_APPROVAL_REQUESTED, [
            'approval_id' => $approval->id,
            'post_id' => $approval->post_id,
            'requested_by' => $approval->requester?->name,
        ]);
    }

    public function approvalApproved($approval): void
    {
        $this->dispatch(Webhook::EVENT_APPROVAL_APPROVED, [
            'approval_id' => $approval->id,
            'post_id' => $approval->post_id,
        ]);
    }

    public function approvalRejected($approval, string $reason): void
    {
        $this->dispatch(Webhook::EVENT_APPROVAL_REJECTED, [
            'approval_id' => $approval->id,
            'post_id' => $approval->post_id,
            'reason' => $reason,
        ]);
    }
}
