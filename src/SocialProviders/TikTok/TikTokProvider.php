<?php

namespace Inovector\Mixpost\SocialProviders\TikTok;

use Illuminate\Http\Request;
use Inovector\Mixpost\Abstracts\SocialProvider;
use Inovector\Mixpost\Http\Resources\AccountResource;
use Inovector\Mixpost\Services\TikTokService;
use Inovector\Mixpost\SocialProviders\TikTok\Concerns\ManagesOAuth;
use Inovector\Mixpost\SocialProviders\TikTok\Concerns\ManagesResources;
use Inovector\Mixpost\Support\SocialProviderPostConfigs;

class TikTokProvider extends SocialProvider
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
        return 'TikTok';
    }

    public static function service(): string
    {
        return TikTokService::class;
    }

    public static function postConfigs(): SocialProviderPostConfigs
    {
        return SocialProviderPostConfigs::make()
            ->simultaneousPosting(true)
            ->minTextChar(0)
            ->maxTextChar(2200)
            ->minPhotos(0)
            ->minVideos(1)  // TikTok requires video
            ->minGifs(0)
            ->maxPhotos(0)  // TikTok is video-only
            ->maxVideos(1)
            ->maxGifs(0)
            ->allowMixingMediaTypes(false);
    }

    public static function externalPostUrl(AccountResource $accountResource): string
    {
        return "https://www.tiktok.com/@{$accountResource->username}/video/{$accountResource->pivot->provider_post_id}";
    }
}
