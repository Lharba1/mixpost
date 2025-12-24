<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Models\Account;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends AuthenticatedController
{
    public function index(Request $request): Response
    {
        $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $stats = $this->getStats($startDate, $endDate);
        $accountStats = $this->getAccountStats($startDate, $endDate);
        $postingActivity = $this->getPostingActivity($startDate, $endDate);

        return Inertia::render('Reports', [
            'stats' => $stats,
            'account_stats' => $accountStats,
            'posting_activity' => $postingActivity,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $stats = $this->getStats($startDate, $endDate);
        $accountStats = $this->getAccountStats($startDate, $endDate);

        $filename = "mixpost-report-{$startDate}-to-{$endDate}.csv";

        return response()->streamDownload(function () use ($stats, $accountStats, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');

            // Report header
            fputcsv($handle, ['Mixpost Report']);
            fputcsv($handle, ["Period: {$startDate} to {$endDate}"]);
            fputcsv($handle, []);

            // Overall statistics
            fputcsv($handle, ['Overall Statistics']);
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Total Posts', $stats['total_posts']]);
            fputcsv($handle, ['Published', $stats['published']]);
            fputcsv($handle, ['Scheduled', $stats['scheduled']]);
            fputcsv($handle, ['Draft', $stats['draft']]);
            fputcsv($handle, ['Failed', $stats['failed']]);
            fputcsv($handle, []);

            // Account breakdown
            fputcsv($handle, ['Account Statistics']);
            fputcsv($handle, ['Account', 'Provider', 'Posts']);
            foreach ($accountStats as $account) {
                fputcsv($handle, [$account['name'], $account['provider'], $account['post_count']]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function getStats(string $startDate, string $endDate): array
    {
        $query = Post::whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ]);

        return [
            'total_posts' => (clone $query)->count(),
            'published' => (clone $query)->where('status', Post::STATUS_PUBLISHED)->count(),
            'scheduled' => (clone $query)->where('status', Post::STATUS_SCHEDULED)->count(),
            'draft' => (clone $query)->where('status', Post::STATUS_DRAFT)->count(),
            'failed' => (clone $query)->where('status', Post::STATUS_FAILED)->count(),
        ];
    }

    protected function getAccountStats(string $startDate, string $endDate): array
    {
        return Account::withCount(['posts' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('mixpost_posts.created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }])
        ->get()
        ->map(fn($account) => [
            'id' => $account->id,
            'name' => $account->name,
            'provider' => $account->provider,
            'image' => $account->image,
            'post_count' => $account->posts_count,
        ])
        ->toArray();
    }

    protected function getPostingActivity(string $startDate, string $endDate): array
    {
        $posts = Post::where('status', Post::STATUS_PUBLISHED)
            ->whereBetween('published_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->selectRaw('DATE(published_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $posts->map(fn($item) => [
            'date' => $item->date,
            'count' => $item->count,
        ])->toArray();
    }
}

