<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use Illuminate\Support\Facades\Route;

$attributes = [
    'namespace'  => 'Overtrue\LaravelWeChat\Controllers\Api',
    'prefix'     => 'api',
    'middleware' => ['api'],
];

Route::group($attributes, function ($router) {

    /**
     * 需要经过验证
     */
    Route::group(['middleware' => ['requestCheck']], function () {

        /**
         * 需要经过签名校验
         */
        Route::group(['middleware' => ['authSign2']], function () {
            //查询微信用户信息
            Route::get('wechat/user', 'UserInfoController@info');

            //模板消息
            Route::post("template_msg", 'TemplateMsgController@send');

            //摇周边
            Route::post("share_around/group", 'ShareAroundController@createGroup');
            Route::get("share_around/group/{groupId}", 'ShareAroundController@groupDetail');
            Route::post("share_around/group/{groupId}/device", 'ShareAroundController@addDevices');
            Route::delete("share_around/group/{groupId}/device", 'ShareAroundController@removeDevices');

            //短网址转换
            Route::post("url", 'OtherController@url');

            //微信统计数据
            //累计用户及新增用户
            Route::post('statistics/user/cumulate_data', 'WechatUserStatisticController@cumulate');
        });

        /**
         * 需要经过授权
         */
        Route::group(['middleware' => ['auth:api']], function () {

            Route::group(["middleware" => ["scopes:mobile-token"]], function () {

            });


            Route::group(["middleware" => ["scope:mobile-token,wechat-token"]], function () {


            });
        });
    });
});
