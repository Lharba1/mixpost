<?php

namespace Inovector\Mixpost\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Inovector\Mixpost\Enums\PostStatus;
use Inovector\Mixpost\Models\Account;
use Inovector\Mixpost\Models\Metric;
use Inovector\Mixpost\Models\Post;

class AnalyticsService
{
    protected ?Carbon $from;
    protected ?Carbon $to;
    protected ?int $accountId = null;

    public function __construct(?Carbon $from = null, ?Carbon $to = null)
    {
        $this->from = $from ?? now()->subDays(30);
        $this->to = $to ?? now();
    }

    public function forAccount(int $accountId): self
    {
        $this->accountId = $accountId;
        return $this;
    }

    /**
     * Get overview statistics
     */
    public function getOverview(): array
    {
        $postsQuery = Post::query()
            ->whereBetween('scheduled_at', [$this->from, $this->to])
            ->where('status', PostStatus::PUBLISHED->value);

        if ($this->accountId) {
            $postsQuery->whereHas('accounts', function ($q) {
                $q->where('account_id', $this->accountId);
            });
        }

        $totalPosts = $postsQuery->count();
        $scheduledPosts = Post::query()
            ->where('status', PostStatus::SCHEDULED->value)
            ->when($this->accountId, fn($q) => $q->whereHas('accounts', fn($aq) => $aq->where('account_id', $this->accountId)))
            ->count();

        $failedPosts = Post::query()
            ->whereBetween('scheduled_at', [$this->from, $this->to])
            ->where('status', PostStatus::FAILED->value)
            ->when($this->accountId, fn($q) => $q->whereHas('accounts', fn($aq) => $aq->where('account_id', $this->accountId)))
            ->count();

        return [
            'total_posts' => $totalPosts,
            'scheduled_posts' => $scheduledPosts,
            'failed_posts' => $failedPosts,
            'period' => [
                'from' => $this->from->toDateString(),
                'to' => $this->to->toDateString(),
            ],
        ];
    }

    /**
     * Get posts per day for charting (database-agnostic)
     */
    public function getPostsPerDay(): array
    {
        $query = Post::query()
            ->select('scheduled_at')
            ->whereBetween('scheduled_at', [$this->from, $this->to])
            ->where('status', PostStatus::PUBLISHED->value);

        if ($this->accountId) {
            $query->whereHas('accounts', fn($q) => $q->where('account_id', $this->accountId));
        }

        $posts = $query->get();
        
        // Group by date using PHP
        $grouped = $posts->groupBy(fn($post) => $post->scheduled_at?->toDateString());

        // Fill in missing dates with 0
        $period = CarbonPeriod::create($this->from, $this->to);
        $data = [];

        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $data[] = [
                'date' => $dateStr,
                'count' => $grouped->get($dateStr)?->count() ?? 0,
                'label' => $date->format('M j'),
            ];
        }

        return $data;
    }

    /**
     * Get posts grouped by hour of day (database-agnostic)
     */
    public function getPostsByHour(): array
    {
        $query = Post::query()
            ->select('scheduled_at')
            ->whereBetween('scheduled_at', [$this->from, $this->to])
            ->where('status', PostStatus::PUBLISHED->value);

        if ($this->accountId) {
            $query->whereHas('accounts', fn($q) => $q->where('account_id', $this->accountId));
        }

        $posts = $query->get();
        
        // Group by hour using PHP
        $grouped = $posts->groupBy(fn($post) => $post->scheduled_at?->format('G'));

        $data = [];
        for ($i = 0; $i < 24; $i++) {
            $data[] = [
                'hour' => $i,
                'label' => sprintf('%02d:00', $i),
                'count' => $grouped->get((string)$i)?->count() ?? 0,
            ];
        }

        return $data;
    }

    /**
     * Get posts grouped by day of week (database-agnostic)
     */
    public function getPostsByDayOfWeek(): array
    {
        $query = Post::query()
            ->select('scheduled_at')
            ->whereBetween('scheduled_at', [$this->from, $this->to])
            ->where('status', PostStatus::PUBLISHED->value);

        if ($this->accountId) {
            $query->whereHas('accounts', fn($q) => $q->where('account_id', $this->accountId));
        }

        $posts = $query->get();
        
        // Group by day of week using PHP (0 = Sunday, 6 = Saturday)
        $grouped = $posts->groupBy(fn($post) => $post->scheduled_at?->dayOfWeek);

        $days = [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];

        $data = [];
        for ($i = 0; $i <= 6; $i++) {
            $data[] = [
                'day' => $i,
                'label' => $days[$i],
                'count' => $grouped->get($i)?->count() ?? 0,
            ];
        }

        return $data;
    }

    /**
     * Get posts per account
     */
    public function getPostsByAccount(): array
    {
        // Get all accounts with their post counts via direct query
        return Account::query()
            ->select('mixpost_accounts.id', 'mixpost_accounts.name', 'mixpost_accounts.provider')
            ->selectRaw('(
                SELECT COUNT(*) FROM mixpost_post_accounts pa
                JOIN mixpost_posts p ON pa.post_id = p.id
                WHERE pa.account_id = mixpost_accounts.id
                AND p.status = ?
                AND p.scheduled_at BETWEEN ? AND ?
            ) as posts_count', [PostStatus::PUBLISHED->value, $this->from, $this->to])
            ->orderByDesc('posts_count')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'name' => $account->name,
                'provider' => $account->provider,
                'posts_count' => $account->posts_count,
            ])
            ->toArray();
    }

    /**
     * Get engagement metrics if available
     */
    public function getEngagementMetrics(): array
    {
        $query = Metric::query()
            ->whereBetween('date', [$this->from->toDateString(), $this->to->toDateString()]);

        if ($this->accountId) {
            $query->where('account_id', $this->accountId);
        }

        return [
            'followers' => $query->clone()->where('type', 'followers')->sum('value'),
            'impressions' => $query->clone()->where('type', 'impressions')->sum('value'),
            'engagement' => $query->clone()->where('type', 'engagement')->sum('value'),
            'likes' => $query->clone()->where('type', 'likes')->sum('value'),
            'comments' => $query->clone()->where('type', 'comments')->sum('value'),
            'shares' => $query->clone()->where('type', 'shares')->sum('value'),
        ];
    }

    /**
     * Get top performing posts
     */
    public function getTopPosts(int $limit = 5): array
    {
        $query = Post::query()
            ->with(['accounts', 'versions'])
            ->whereBetween('scheduled_at', [$this->from, $this->to])
            ->where('status', PostStatus::PUBLISHED->value);

        if ($this->accountId) {
            $query->whereHas('accounts', fn($q) => $q->where('account_id', $this->accountId));
        }

        return $query
            ->orderByDesc('scheduled_at')
            ->limit($limit)
            ->get()
            ->map(fn($post) => [
                'id' => $post->id,
                'uuid' => $post->uuid,
                'preview' => substr($post->versions->first()?->content[0]['body'] ?? '', 0, 100),
                'scheduled_at' => $post->scheduled_at?->format('M j, Y g:i A'),
                'accounts' => $post->accounts->pluck('name'),
            ])
            ->toArray();
    }

    /**
     * Get full analytics report
     */
    public function getFullReport(): array
    {
        return [
            'overview' => $this->getOverview(),
            'posts_per_day' => $this->getPostsPerDay(),
            'posts_by_hour' => $this->getPostsByHour(),
            'posts_by_day_of_week' => $this->getPostsByDayOfWeek(),
            'posts_by_account' => $this->getPostsByAccount(),
            'engagement' => $this->getEngagementMetrics(),
            'top_posts' => $this->getTopPosts(),
        ];
    }
}
