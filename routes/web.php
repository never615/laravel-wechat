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

    Route::get('/', 'Admin\HomeController@welcome');

    //----------------------------------------  微信开始   -----------------------------------------------
    Route::group(['prefix' => "wechat"], function () {

//        开放平台回调,接收component_ticket等
//        Route::any('/platform/callback', '\Overtrue\LaravelWechat\Controllers\WechatOpenPlatformController@serve');


        //第三方公众号请求授权
        Route::get('/platform/auth', '\Overtrue\LaravelWechat\Controllers\WechatOpenPlatformController@auth');
        //第三方公众号请求授权回调
        Route::get('/platform/auth/callback',
            '\Overtrue\LaravelWechat\Controllers\WechatOpenPlatformController@authCallback');


        Route::post('{appid}/callback',
            '\Overtrue\LaravelWechat\Controllers\WechatOpenPlatformController@wechatCallback');

        //jsconfig
        Route::get('/jsconfig', '\Overtrue\LaravelWechat\Controllers\WechatOpenPlatformController@jsConfig');


        Route::group(['middleware' => ['wechat.open_platform_oauth']], function ($route) {

            //微信授权中心,获取微信用户授权的信息
            //获得openid
            Route::get("oauth", '\Overtrue\LaravelWechat\Controllers\WechatOAuthController@oauth');
            Route::get("oauth/info", '\Overtrue\LaravelWechat\Controllers\WechatOAuthController@oauth');
            //test
            Route::get('/user1', '\Overtrue\LaravelWechat\Controllers\WechatOAuthController@userTest');
        });
    });
    //----------------------------------------  微信结束   -----------------------------------------------

//----------------------------------------  管理端开始  -----------------------------------------------


    Route::group(['prefix' => config('admin.prefix'), "middleware" => ["admin"]], function ($router) {

        $router->get('/', 'Admin\HomeController@index')->name("dashboard");

        Route::group(["middleware" => ['admin.permission:allow,owner']], function ($router) {
            $router->get('log', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index')->name("log");
        });
        $router->resource("wechat_auth_infos", 'Admin\AuthInfoController');
        $router->resource("wechat_user_infos", 'Admin\UserInfoController');
    });

//----------------------------------------  管理端结束  -----------------------------------------------


});
