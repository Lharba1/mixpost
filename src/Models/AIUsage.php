<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIUsage extends Model
{
    use HasFactory;

    public $table = 'mixpost_ai_usage';

    protected $fillable = [
        'provider',
        'model',
        'action',
        'tokens_used',
        'cost',
        'metadata',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
        'cost' => 'decimal:6',
        'metadata' => 'array',
    ];

    /**
     * Get usage statistics for a period
     */
    public static function getStats(?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now();

        $query = static::whereBetween('created_at', [$from, $to]);

        return [
            'total_requests' => $query->count(),
            'total_tokens' => $query->sum('tokens_used'),
            'total_cost' => $query->sum('cost'),
            'by_action' => $query->selectRaw('action, COUNT(*) as count, SUM(tokens_used) as tokens')
                ->groupBy('action')
                ->get()
                ->keyBy('action')
                ->toArray(),
            'by_provider' => $query->selectRaw('provider, COUNT(*) as count')
                ->groupBy('provider')
                ->get()
                ->keyBy('provider')
                ->toArray(),
        ];
    }
}
