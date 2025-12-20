<?php

namespace Inovector\Mixpost\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Http\Resources\AccountResource;
use Inovector\Mixpost\Models\Account;
use Inovector\Mixpost\Services\AnalyticsService;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $from = $request->input('from') ? Carbon::parse($request->input('from')) : now()->subDays(30);
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : now();
        $accountId = $request->input('account_id');

        $analytics = new AnalyticsService($from, $to);
        
        if ($accountId) {
            $analytics->forAccount($accountId);
        }

        return Inertia::render('Analytics', [
            'analytics' => $analytics->getFullReport(),
            'accounts' => AccountResource::collection(Account::all())->resolve(),
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'account_id' => $accountId,
            ],
        ]);
    }

    /**
     * API endpoint for chart data
     */
    public function chartData(Request $request): JsonResponse
    {
        $from = $request->input('from') ? Carbon::parse($request->input('from')) : now()->subDays(30);
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : now();
        $accountId = $request->input('account_id');
        $type = $request->input('type', 'posts_per_day');

        $analytics = new AnalyticsService($from, $to);
        
        if ($accountId) {
            $analytics->forAccount($accountId);
        }

        $data = match ($type) {
            'posts_per_day' => $analytics->getPostsPerDay(),
            'posts_by_hour' => $analytics->getPostsByHour(),
            'posts_by_day_of_week' => $analytics->getPostsByDayOfWeek(),
            'posts_by_account' => $analytics->getPostsByAccount(),
            'engagement' => $analytics->getEngagementMetrics(),
            'overview' => $analytics->getOverview(),
            default => $analytics->getFullReport(),
        };

        return response()->json($data);
    }

    /**
     * Export analytics data
     */
    public function export(Request $request): JsonResponse
    {
        $from = $request->input('from') ? Carbon::parse($request->input('from')) : now()->subDays(30);
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : now();
        $accountId = $request->input('account_id');

        $analytics = new AnalyticsService($from, $to);
        
        if ($accountId) {
            $analytics->forAccount($accountId);
        }

        return response()->json([
            'data' => $analytics->getFullReport(),
            'exported_at' => now()->toDateTimeString(),
        ]);
    }
}
