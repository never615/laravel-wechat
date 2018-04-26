
## 开放平台需要注册的路由
```
 Route::group(['prefix' => "wechat"], function () {

        //开放平台回调,接收component_ticket等
        Route::any('/platform/callback', '\Overtrue\LaravelWeChat\Controllers\WechatOpenPlatformController@serve');
        //第三方公众号请求授权
        Route::get('/platform/auth', '\Overtrue\LaravelWeChat\Controllers\WechatOpenPlatformController@auth');
        //第三方公众号请求授权回调
        Route::get('/platform/auth/callback',
            '\Overtrue\LaravelWeChat\Controllers\WechatOpenPlatformController@authCallback');

        Route::group(['middleware' => ['wechat.public_oauth']], function ($route) {

            //微信登录
            Route::get("login", 'Auth\WechatLoginController@wechatLogin');
            Route::get("login_with_mobile", 'Auth\WechatLoginController@wechatLoginWithMobile');
            Route::get("login_with_email", 'Auth\WechatLoginController@wechatLoginWithEmail');

        });


    });


 Route::group(['prefix' => config('admin.route.prefix'), "middleware" => ["admin"]], function ($router) {
        //corp
         $router->resource("corp_auth_infos", '\Overtrue\LaravelWeChat\Controllers\Admin\CorpAuthInfoController');
                $router->resource("corp_user_infos", '\Overtrue\LaravelWeChat\Controllers\Admin\CorpUserInfoController');

        $router->resource("wechat_auth_infos", '\Overtrue\LaravelWeChat\Controllers\Admin\AuthInfoController');
        $router->resource("wechat_user_infos", '\Overtrue\LaravelWeChat\Controllers\Admin\UserInfoController');
 });

```
