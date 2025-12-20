<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class Variable extends Model
{
    use HasFactory;
    use HasUuid;

    public $table = 'mixpost_variables';

    protected $fillable = [
        'name',
        'key',
        'value',
    ];

    /**
     * Get built-in system variables
     */
    public static function getSystemVariables(): array
    {
        $now = now();
        
        return [
            [
                'key' => 'date',
                'name' => 'Current Date',
                'value' => $now->format('Y-m-d'),
                'system' => true,
            ],
            [
                'key' => 'time',
                'name' => 'Current Time',
                'value' => $now->format('H:i'),
                'system' => true,
            ],
            [
                'key' => 'day',
                'name' => 'Day of Week',
                'value' => $now->format('l'),
                'system' => true,
            ],
            [
                'key' => 'month',
                'name' => 'Current Month',
                'value' => $now->format('F'),
                'system' => true,
            ],
            [
                'key' => 'year',
                'name' => 'Current Year',
                'value' => $now->format('Y'),
                'system' => true,
            ],
            [
                'key' => 'day_number',
                'name' => 'Day Number',
                'value' => $now->format('d'),
                'system' => true,
            ],
            [
                'key' => 'month_number',
                'name' => 'Month Number',
                'value' => $now->format('m'),
                'system' => true,
            ],
        ];
    }

    /**
     * Get all variables (system + custom)
     */
    public static function getAllVariables(): array
    {
        $systemVariables = self::getSystemVariables();
        
        $customVariables = self::all()->map(function ($variable) {
            return [
                'id' => $variable->id,
                'uuid' => $variable->uuid,
                'key' => $variable->key,
                'name' => $variable->name,
                'value' => $variable->value,
                'system' => false,
            ];
        })->toArray();

        return array_merge($systemVariables, $customVariables);
    }
}
