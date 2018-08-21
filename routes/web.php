<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

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
    'namespace'  => 'Overtrue\LaravelWeChat\Controllers',
    'middleware' => ['web'],
];

Route::group($attributes, function ($router) {

    //----------------------------------------  微信开始   -----------------------------------------------
    Route::group(['prefix' => "wechat"], function () {

        if (config("wechat.mode") === "open_platform") {


//        开放平台回调,接收component_ticket等
//        Route::any('/platform/callback', 'WechatOpenPlatformController@serve');


            //第三方公众号请求授权
            Route::get('/platform/auth', 'WechatOpenPlatformController@auth');
            //第三方公众号请求授权回调
            Route::get('/platform/auth/callback', 'WechatOpenPlatformController@authCallback');


            Route::post('{appid}/callback', 'WechatOpenPlatformController@wechatCallback');

            //jsconfig
            Route::get('/jsconfig', 'WechatOpenPlatformController@jsConfig');


            Route::group(['middleware' => ['wechat.open_platform_oauth']], function ($route) {

                //微信授权中心,获取微信用户授权的信息
                //获得openid
                Route::get("oauth", 'WechatOAuthController@oauth');
                Route::get("oauth/info", 'WechatOAuthController@oauth');
                //test
                Route::get('/user1', 'WechatOAuthController@userTest');
            });
        } else {
            //wechat.mode : official_account
            //jsconfig
            Route::get('/jsconfig', 'OfficialAccountController@jsConfig');


            Route::group(['middleware' => ['wechat.oauth']], function ($route) {

                //微信授权中心,获取微信用户授权的信息
                //获得openid
                Route::get("oauth", 'OfficialAccountController@oauth');
                Route::get("oauth/info", 'OfficialAccountController@oauth');
                //test
                Route::get('/user1', 'OfficialAccountController@userTest');
            });
        }
    });
    //----------------------------------------  微信结束   -----------------------------------------------


//----------------------------------------  管理端开始  -----------------------------------------------


    Route::group(['prefix' => config('admin.route.prefix'), "middleware" => ["adminE"]], function ($router) {

        $router->get('log', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index')->name("log");

        $router->resource("wechat_auth_infos", 'Admin\AuthInfoController');
        $router->resource("wechat_user_infos", 'Admin\UserInfoController');

        $router->post('wechat/user/cumulates', 'Admin\WechatUserController@cumulateUserData');
        $router->post('wechat/user/new', 'Admin\WechatUserController@newUserData');

    });

//----------------------------------------  管理端结束  -----------------------------------------------


});
