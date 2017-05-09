<?php

namespace CultureKings\ShopifyAuth\Services;

use CultureKings\ShopifyAuth\Models\ShopifyScriptTag;
use CultureKings\ShopifyAuth\Models\ShopifyUser;
use Illuminate;
use Oseintow\Shopify\Shopify;

/**
 * Class ShopifyAuthService.
 */
class ShopifyAuthService
{
    protected $logger;
    protected $shopify;

    public function __construct(
        Illuminate\Log\Writer $logger,
        Shopify $shopify
    ) {
        $this->logger = $logger;
        $this->shopify = $shopify;
    }

    public function getAccessTokenAndCreateNewUser($code, $shopUrl, $shopifyAppConfig)
    {
        $accessToken = $this->shopify
            ->setKey($shopifyAppConfig['key'])
            ->setSecret($shopifyAppConfig['secret'])
            ->setShopUrl($shopUrl)
            ->getAccessToken($code);

        // store permanent token in DB
        $shopifyUser = ShopifyUser::where('shop_url', $shopUrl)->with('scriptTags');

        // @todo call shopify to get shop info

        if ($shopifyUser->count() === 0) {
            $shopifyUser = ShopifyUser::updateOrCreate([
                'shop_url' => $shopUrl,
                'shop_name' => '',
                'shop_domain' => '',
                'access_token' => $accessToken,
            ]);
        } else {
            $shopifyUser = $shopifyUser->first();
            $shopifyUser->access_token = $accessToken;
            $shopifyUser->save();
        }

        return $shopifyUser;
    }

    public function createScriptTag($shopUrl, $accessToken, $shopifyUser, string $appName, array $scriptTags, $shopifyAppConfig)
    {
        // if script tag already exists in DB, return true
        foreach ($shopifyUser->scriptTags as $tag) {
            if ($tag->shopify_app === $appName) return true;
        }

        $scriptTag = $this->shopify
            ->setKey($shopifyAppConfig['key'])
            ->setSecret($shopifyAppConfig['secret'])
            ->setShopUrl($shopUrl)
            ->setAccessToken($accessToken)
            ->post('admin/script_tags.json', $scriptTags);

        ShopifyScriptTag::create([
            'shop_url' => $shopUrl,
            'script_tag_id' => $scriptTag->get('id'),
            'shopify_users_id' => $shopifyUser->id,
            'shopify_app' => 'launch_countdown',
        ]);

        return true;
    }
}