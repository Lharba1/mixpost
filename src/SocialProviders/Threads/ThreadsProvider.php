<?php

namespace Inovector\Mixpost\SocialProviders\Threads;

use Illuminate\Http\Request;
use Inovector\Mixpost\Abstracts\SocialProvider;
use Inovector\Mixpost\Http\Resources\AccountResource;
use Inovector\Mixpost\Services\ThreadsService;
use Inovector\Mixpost\SocialProviders\Threads\Concerns\ManagesOAuth;
use Inovector\Mixpost\SocialProviders\Threads\Concerns\ManagesResources;
use Inovector\Mixpost\Support\SocialProviderPostConfigs;

class ThreadsProvider extends SocialProvider
{
    use ManagesOAuth;
    use ManagesResources;

    public array $callbackResponseKeys = ['code', 'state'];

    public function __construct(Request $request, string $clientId, string $clientSecret, string $redirectUrl, array $values = [])
    {
        parent::__construct($request, $clientId, $clientSecret, $redirectUrl, $values);
    }

    public static function name(): string
    {
        return 'Threads';
    }

    public static function service(): string
    {
        return ThreadsService::class;
    }

    public static function postConfigs(): SocialProviderPostConfigs
    {
        return SocialProviderPostConfigs::make()
            ->simultaneousPosting(true)
            ->minTextChar(1)
            ->maxTextChar(500)
            ->minPhotos(0)
            ->minVideos(0)
            ->minGifs(0)
            ->maxPhotos(10)
            ->maxVideos(1)
            ->maxGifs(1)
            ->allowMixingMediaTypes(false);
    }

    public static function externalPostUrl(AccountResource $accountResource): string
    {
        return "https://www.threads.net/@{$accountResource->username}/post/{$accountResource->pivot->provider_post_id}";
    }
}
