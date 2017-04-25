
## 开放平台需要注册的路由
```
 Route::group(['prefix' => "wechat"], function () {

        //开放平台回调,接收component_ticket等
        Route::any('/platform/callback', '\Overtrue\LaravelWechat\Controllers\WechatOpenPlatformController@serve');
        //第三方公众号请求授权
        Route::get('/platform/auth', '\Overtrue\LaravelWechat\Controllers\WechatOpenPlatformController@auth');
        //第三方公众号请求授权回调
        Route::get('/platform/auth/callback',
            '\Overtrue\LaravelWechat\Controllers\WechatOpenPlatformController@authCallback');

        Route::group(['middleware' => ['mall.wechat.public_oauth']], function ($route) {

            //微信登录
            Route::get("login", 'Auth\WechatLoginController@wechatLogin');
            Route::get("login_with_mobile", 'Auth\WechatLoginController@wechatLoginWithMobile');
            Route::get("login_with_email", 'Auth\WechatLoginController@wechatLoginWithEmail');

            //test
            Route::get('/user1', function () {
                $user = session('wechat.oauth_user'); // 拿到授权用户资料
                dd($user);
            });




        });


    });


 Route::group(['prefix' => config('admin.prefix'), "middleware" => ["admin"]], function ($router) {
     $router->resource("wechat_auth_infos", "\Overtrue\LaravelWechat\Controllers\Admin\AuthInfoController");
     $router->resource("wechat_user_infos", "\Overtrue\LaravelWechat\Controllers\Admin\UserInfoController");
 });

```
