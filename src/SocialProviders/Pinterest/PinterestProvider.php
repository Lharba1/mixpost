<?php

namespace Inovector\Mixpost\SocialProviders\Pinterest;

use Illuminate\Http\Request;
use Inovector\Mixpost\Abstracts\SocialProvider;
use Inovector\Mixpost\Http\Resources\AccountResource;
use Inovector\Mixpost\Services\PinterestService;
use Inovector\Mixpost\SocialProviders\Pinterest\Concerns\ManagesOAuth;
use Inovector\Mixpost\SocialProviders\Pinterest\Concerns\ManagesResources;
use Inovector\Mixpost\Support\SocialProviderPostConfigs;

class PinterestProvider extends SocialProvider
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
        return 'Pinterest';
    }

    public static function service(): string
    {
        return PinterestService::class;
    }

    public static function postConfigs(): SocialProviderPostConfigs
    {
        return SocialProviderPostConfigs::make()
            ->simultaneousPosting(true)
            ->minTextChar(0)
            ->maxTextChar(500)
            ->minPhotos(1)  // Pinterest requires an image
            ->minVideos(0)
            ->minGifs(0)
            ->maxPhotos(1)
            ->maxVideos(1)  // Pinterest supports video pins too
            ->maxGifs(1)
            ->allowMixingMediaTypes(false);
    }

    public static function externalPostUrl(AccountResource $accountResource): string
    {
        return "https://www.pinterest.com/pin/{$accountResource->pivot->provider_post_id}/";
    }

    /**
     * Pinterest has entities (boards) that users can select
     */
    public bool $onlyUserAccount = false;

    public function getEntities(): array
    {
        $boards = $this->getBoards();

        if (isset($boards['error'])) {
            return $boards;
        }

        return collect($boards)->map(function ($board) {
            return [
                'id' => $board['id'],
                'name' => $board['name'],
            ];
        })->toArray();
    }
}
