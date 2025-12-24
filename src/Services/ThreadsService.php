<?php

namespace Inovector\Mixpost\Services;

use Inovector\Mixpost\Abstracts\Service;

class ThreadsService extends Service
{
    public static function name(): string
    {
        return 'threads';
    }

    public static function credentialsFormSchema(): array
    {
        return [
            'client_id' => [
                'label' => 'App ID',
                'type' => 'text',
                'required' => true,
                'help' => 'Use your Meta/Instagram App ID',
            ],
            'client_secret' => [
                'label' => 'App Secret',
                'type' => 'text',
                'required' => true,
            ],
        ];
    }
}
