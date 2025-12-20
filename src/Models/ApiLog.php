<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    use HasFactory;

    public $table = 'mixpost_api_logs';

    protected $fillable = [
        'token_id',
        'method',
        'endpoint',
        'response_code',
        'ip_address',
        'user_agent',
        'response_time',
    ];

    /**
     * The token used for this request
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(ApiToken::class, 'token_id');
    }

    /**
     * Log an API request
     */
    public static function log(
        ?int $tokenId,
        string $method,
        string $endpoint,
        int $responseCode,
        ?int $responseTime = null
    ): self {
        return static::create([
            'token_id' => $tokenId,
            'method' => strtoupper($method),
            'endpoint' => $endpoint,
            'response_code' => $responseCode,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'response_time' => $responseTime,
        ]);
    }
}
