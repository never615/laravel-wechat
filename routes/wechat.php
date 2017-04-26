<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/


use Illuminate\Support\Facades\Route;

$attributes = [
    'namespace'  => 'Mallto\Mall\Controller',
    'middleware' => ['web'],
];

Route::group($attributes, function ($router) {

    Route::get('/test', 'TestController@index');


    //----------------------------------------  微信开始   -----------------------------------------------
    Route::group(['prefix' => "wechat"], function () {

        //开放平台回调,接收component_ticket等
        Route::any('/platform/callback', '\Overtrue\LaravelWechat\Controllers\WechatOpenPlatformController@serve');
        //第三方公众号请求授权
        Route::get('/platform/auth', '\Overtrue\LaravelWechat\Controllers\WechatOpenPlatformController@auth');
        //第三方公众号请求授权回调
        Route::get('/platform/auth/callback',
            '\Overtrue\LaravelWechat\Controllers\WechatOpenPlatformController@authCallback');

        Route::group(['middleware' => ['wechat.public_oauth']], function ($route) {

            //微信授权中心,获取微信用户授权的信息
            Route::get("oauth","WechatOAuthController@oauth");
            
            
            //微信登录
//            Route::get("login", 'Auth\WechatLoginController@wechatLogin');
//            Route::get("login_with_mobile", 'Auth\WechatLoginController@wechatLoginWithMobile');
//            Route::get("login_with_email", 'Auth\WechatLoginController@wechatLoginWithEmail');

            //test
            Route::get('/user1', function () {
                $user = session('wechat.oauth_user'); // 拿到授权用户资料
                dd($user);
            });

        });


    });





//----------------------------------------  微信结束   -----------------------------------------------
});





