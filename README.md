# shopify-app-auth-laravel
Laravel Auth Boilerplate for Shopify App

## Installation
### Install
`composer require culturekings/shopify-app-auth-laravel`

### Add to Providers
Add to Providers in config/app.php
`CultureKings\ShopifyAuth\ShopifyAuthServiceProvider::class`

### Publish
`php artisan vendor:publish`

### Setup Views
#### Install Success page
In your resources/views folder, create your folder and install-sucess.blade.php file, and then within `shopify-auth.app_name`, set your `view_install_success_path` value to whatever it is (see below in configure app for example).

### Auth Middleware
Set the middleware on routes - ensure that ShopifyAuthCheck if sitting around the routes. Web too, but I think that is standard in the web.php file.
```php
Route::group(
    [
        'prefix' => 'shopify-apps',
        'namespace' => 'ShopifyApps',
        'middleware' => 'CultureKings\ShopifyAuth\Http\Middleware\ShopifyAuthCheck'
    ],
    function () {
        Route::get('appname', 'AppnameController@getDashboard');
    }
);
```

### Configure App in config
Once published, set up your app.

You can see below that everything is setup under "appname" and then app_name is used from then on.

In the routes, you see that appName is passed as a variable in the auth url, so this is very important that url is the same as the array key of "appname". 

You can change this to be whatever you like so you can run multiple apps through a single auth flow.
```php
'appname' => [
    'name' => 'app_name', // checked in db, so shouldn't change after launch
    'price' => 0.00,
    'redirect_url' => '/shopify-auth/app_name/auth/callback', // relative uri
    'success_url' => '/shopify-auth/app_name/install/success',
    'scope' => [
        "write_products",
        "write_script_tags"
    ],
    'view_folder_path' => 'shopify-apps.app_name',
    'view_install_success_path' => 'shopify-apps.app_name.install-success',
    'key' => env("SHOPIFY_APPNAME_APIKEY"),
    'secret' => env("SHOPIFY_APPNAME_SECRET"),
],
```


## Usage
All shopify calls should be made through a service and make a call similar to below:
```php
// $appName comes from url passed into method as param
$shopifyAppConfig = config('shopify-auth.'.$appName);

/ call shopify api
$this->shopify
    ->setKey($shopifyAppConfig['key'])
    ->setSecret($shopifyAppConfig['secret'])
    ->setShopUrl($shopUrl)
    ->setAccessToken($accessToken)
    ->post('admin/script_tags.json', $scriptTags);
```

### Authenticate Shop App
Navigate to: `http://exampledomain.dev/shopify-auth/app_name/install?shop=shopifydomain.myshopify.com`

It will then redirect you to your shops auth process to start


### Common Controller Setup
I'd generally use this as the construct:
```php
public function __construct(ShopifyApi $shopify, Request $request, ShopifyAuthService $shopifyAuthService)
{
     $this->shopify = $shopify;
     $this->shopifySession = $request->session()->get('shopifyapp');
     $this->shopifyAuthService = $shopifyAuthService;
 }
```

Then set up a method:
```php
public function getDashboard()
{
    $shopUrl = $this->shopifySession['shop_url'];
    $appName = $this->shopifySession['app_name'];

    // find user, then get countdowns based on that
    $user = ShopifyUser::where([
        'shop_url' => $shopUrl,
        'app_name' => $appName,
    ])->first();

    $appConfig = config('shopify-auth.' . $appName);

    $this->checkExistsAndCreateScriptTag($shopUrl, $user->access_token, $user, $appName);

    return view('app_name.dashboard')->with([
        'app_key' => config($appConfig['key']),
    ]);
}
```
