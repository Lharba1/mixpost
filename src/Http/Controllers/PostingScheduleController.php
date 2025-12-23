<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Http\Resources\PostingScheduleResource;
use Inovector\Mixpost\Http\Resources\QueueItemResource;
use Inovector\Mixpost\Models\PostingSchedule;
use Inovector\Mixpost\Models\PostingScheduleTime;
use Inovector\Mixpost\Models\QueueItem;

class PostingScheduleController extends Controller
{
    public function index(): Response
    {
        $schedule = PostingSchedule::with('times')->first();
        
        // Create default schedule if none exists
        if (!$schedule) {
            $schedule = PostingSchedule::create(['name' => 'Default Schedule']);
            $schedule->load('times'); // Load empty times relationship
        }

        $queueItems = QueueItem::with(['post.versions', 'post.accounts', 'scheduleTime'])
            ->pending()
            ->ordered()
            ->get();

        return Inertia::render('PostingSchedule', [
            'schedule' => new PostingScheduleResource($schedule),
            'queue_items' => QueueItemResource::collection($queueItems)->resolve(),
            'days' => PostingScheduleTime::DAY_NAMES,
        ]);
    }

    public function addTimeSlot(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'time' => 'required|date_format:H:i',
        ]);

        $schedule = PostingSchedule::firstOrCreate(
            [],
            ['name' => 'Default Schedule']
        );

        $schedule->times()->create([
            'day_of_week' => $validated['day_of_week'],
            'time' => $validated['time'] . ':00',
            'is_active' => true,
        ]);

        return redirect()->back();
    }

    public function removeTimeSlot(PostingScheduleTime $time): RedirectResponse
    {
        $time->delete();

        return redirect()->back();
    }

    public function toggleTimeSlot(PostingScheduleTime $time): HttpResponse
    {
        $time->update(['is_active' => !$time->is_active]);

        return response()->noContent();
    }

    /**
     * Add a post to the queue
     */
    public function addToQueue(Request $request): HttpResponse
    {
        $validated = $request->validate([
            'post_id' => 'required|exists:mixpost_posts,id',
            'schedule_time_id' => 'nullable|exists:mixpost_posting_schedule_times,id',
            'scheduled_at' => 'nullable|date',
        ]);

        $schedule = PostingSchedule::with('times')->first();
        
        $scheduledAt = null;
        
        if (!empty($validated['scheduled_at'])) {
            $scheduledAt = $validated['scheduled_at'];
        } elseif ($schedule && !empty($validated['schedule_time_id'])) {
            $scheduleTime = PostingScheduleTime::find($validated['schedule_time_id']);
            $scheduledAt = $schedule->getNextPublishDate($scheduleTime);
        } elseif ($schedule) {
            $nextSlot = $schedule->getNextAvailableSlot();
            if ($nextSlot) {
                $scheduledAt = $schedule->getNextPublishDate($nextSlot);
            }
        }

        $maxPosition = QueueItem::pending()->max('position') ?? 0;

        $queueItem = QueueItem::create([
            'post_id' => $validated['post_id'],
            'schedule_time_id' => $validated['schedule_time_id'] ?? null,
            'scheduled_at' => $scheduledAt,
            'position' => $maxPosition + 1,
            'status' => 'pending',
        ]);

        return response()->json([
            'queue_item' => new QueueItemResource($queueItem),
        ], 201);
    }

    /**
     * Remove a post from the queue
     */
    public function removeFromQueue(QueueItem $queueItem): RedirectResponse
    {
        $queueItem->delete();

        return redirect()->back();
    }

    /**
     * Reorder queue items
     */
    public function reorderQueue(Request $request): HttpResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:mixpost_queue,id',
            'items.*.position' => 'required|integer|min:0',
        ]);

        foreach ($validated['items'] as $item) {
            QueueItem::where('id', $item['id'])->update(['position' => $item['position']]);
        }

        return response()->noContent();
    }

    /**
     * Retry a failed queue item
     */
    public function retryQueueItem(QueueItem $queueItem): RedirectResponse
    {
        $queueItem->requeue();

        return redirect()->back();
    }

    /**
     * Get queue statistics
     */
    public function stats(): HttpResponse
    {
        return response()->json([
            'pending' => QueueItem::pending()->count(),
            'published_today' => QueueItem::where('status', 'published')
                ->whereDate('updated_at', today())
                ->count(),
            'failed' => QueueItem::where('status', 'failed')->count(),
        ]);
    }
}
