<?php

namespace Inovector\Mixpost\Services;

use Inovector\Mixpost\Abstracts\Service;

class PinterestService extends Service
{
    public static function name(): string
    {
        return 'pinterest';
    }

    public static function credentialsFormSchema(): array
    {
        return [
            'client_id' => [
                'label' => 'App ID',
                'type' => 'text',
                'required' => true,
            ],
            'client_secret' => [
                'label' => 'App Secret',
                'type' => 'text',
                'required' => true,
            ],
        ];
    }
}
