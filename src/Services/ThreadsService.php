<?php

namespace Inovector\Mixpost\Services;

use Inovector\Mixpost\Abstracts\Service;
use Inovector\Mixpost\Enums\ServiceGroup;

class ThreadsService extends Service
{
    public static array $exposedFormAttributes = [];

    public static function group(): ServiceGroup
    {
        return ServiceGroup::SOCIAL;
    }

    public static function name(): string
    {
        return 'threads';
    }

    static function form(): array
    {
        return [
            'client_id' => '',
            'client_secret' => '',
        ];
    }

    public static function formRules(): array
    {
        return [
            'client_id' => ['required'],
            'client_secret' => ['required'],
        ];
    }

    public static function formMessages(): array
    {
        return [
            'client_id' => 'The App ID is required.',
            'client_secret' => 'The App Secret is required.',
        ];
    }
}
