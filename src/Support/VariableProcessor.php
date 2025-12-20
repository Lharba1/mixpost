<?php

namespace Inovector\Mixpost\Support;

use Inovector\Mixpost\Models\Variable;

class VariableProcessor
{
    /**
     * Process content and replace variable placeholders with actual values
     */
    public static function process(string $content): string
    {
        $variables = Variable::getAllVariables();

        foreach ($variables as $variable) {
            $placeholder = '{' . $variable['key'] . '}';
            
            // For system variables, get fresh value at processing time
            if ($variable['system'] ?? false) {
                $value = self::getSystemVariableValue($variable['key']);
            } else {
                $value = $variable['value'];
            }
            
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    /**
     * Get fresh system variable value
     */
    protected static function getSystemVariableValue(string $key): string
    {
        $now = now();

        return match ($key) {
            'date' => $now->format('Y-m-d'),
            'time' => $now->format('H:i'),
            'day' => $now->format('l'),
            'month' => $now->format('F'),
            'year' => $now->format('Y'),
            'day_number' => $now->format('d'),
            'month_number' => $now->format('m'),
            default => '',
        };
    }

    /**
     * Extract variable keys from content
     */
    public static function extractVariables(string $content): array
    {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $content, $matches);
        
        return array_unique($matches[1] ?? []);
    }

    /**
     * Preview content with variables replaced
     */
    public static function preview(string $content): string
    {
        return self::process($content);
    }

    /**
     * Validate that all variables in content exist
     */
    public static function validateVariables(string $content): array
    {
        $usedVariables = self::extractVariables($content);
        $availableVariables = collect(Variable::getAllVariables())->pluck('key')->toArray();
        
        $missing = array_diff($usedVariables, $availableVariables);
        
        return [
            'valid' => empty($missing),
            'missing' => array_values($missing),
        ];
    }
}
