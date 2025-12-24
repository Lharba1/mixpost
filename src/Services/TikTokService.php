<?php

namespace Inovector\Mixpost\Services;

use Inovector\Mixpost\Abstracts\Service;

class TikTokService extends Service
{
    public static function name(): string
    {
        return 'tiktok';
    }

    public static function credentialsFormSchema(): array
    {
        return [
            'client_id' => [
                'label' => 'Client Key',
                'type' => 'text',
                'required' => true,
            ],
            'client_secret' => [
                'label' => 'Client Secret',
                'type' => 'text',
                'required' => true,
            ],
        ];
    }
}
