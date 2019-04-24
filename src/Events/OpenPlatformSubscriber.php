<?php

/*
 * This file is part of the overtrue/laravel-wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\LaravelWeChat\Events;

use Overtrue\LaravelWeChat\Domain\OpenPlatformAuthUsecase;

class OpenPlatformSubscriber
{
    /**
     * @var OpenPlatformAuthUsecase
     */
    private $openPlatformAuthUsecase;


    /**
     * WechatOpenPlatformController constructor.
     *
     * @param OpenPlatformAuthUsecase $openPlatformAuthUsecase
     */
    public function __construct(OpenPlatformAuthUsecase $openPlatformAuthUsecase)
    {
        $this->openPlatformAuthUsecase = $openPlatformAuthUsecase;
    }

    /**
     * 授权方成功授权
     */
    public function Authorized($event)
    {
        $playload = $event->payload;
        \Log::info("authorized");
        \Log::info($playload);

        $this->openPlatformAuthUsecase->createOrUpdateAuthInfo($playload["AuthorizationCode"]);


    }

    /**
     * 授权方更新授权
     */
    public function UpdateAuthorized($event)
    {
        $playload = $event->payload;
        \Log::info("updateauthorized");
        \Log::info($playload);

        $this->openPlatformAuthUsecase->createOrUpdateAuthInfo($playload["AuthorizationCode"]);

//        array (
//            'AppId' => 'wxc40ca2518c77b327',
//            'CreateTime' => '1519373459',
//            'InfoType' => 'authorized',
//            'AuthorizerAppid' => 'wx4de28798edb298cb',
//            'AuthorizationCode' => 'queryauthcode@@@jKz5tqlqNWMyQwPtsdOFhEtGlsJSCaLcAhdnmQB0q6k5Hv81K1vw0ZvUE7O6eVRfCMZ7X0JUtJTWF_IhNCqGRA',
//            'AuthorizationCodeExpiredTime' => '1519377059',
//            'PreAuthCode' => 'preauthcode@@@rlRmD-f3bX1oZOOzVZNL_irlFCqPqQdLixsx5To1lhKBRkhcyutGCvWsU59b9OXp',
//        ) 
    }

    /**
     * 授权方取消授权
     */
    public function Unauthorized($event)
    {
        $playload = $event->payload;

        \Log::info("unauthorized");
        \Log::info($playload);

        $this->openPlatformAuthUsecase->unAuth($playload["AuthorizerAppid"]);
    }

    /**
     * 开放平台推送 VerifyTicket
     */
    public function VerifyTicketRefreshed($event)
    {
//        \Log::info("component_verify_ticket");
    }

    /**
     * 为订阅者注册监听器.
     *
     */
    public function subscribe($events)
    {
        $events->listen(
            'Overtrue\LaravelWeChat\Events\OpenPlatform\Authorized',
            'Overtrue\LaravelWeChat\Events\OpenPlatformSubscriber@Authorized'
        );

        $events->listen(
            'Overtrue\LaravelWeChat\Events\OpenPlatform\UpdateAuthorized',
            'Overtrue\LaravelWeChat\Events\OpenPlatformSubscriber@UpdateAuthorized'
        );

        $events->listen(
            'Overtrue\LaravelWeChat\Events\OpenPlatform\Unauthorized',
            'Overtrue\LaravelWeChat\Events\OpenPlatformSubscriber@Unauthorized'
        );

        $events->listen(
            'Overtrue\LaravelWeChat\Events\OpenPlatform\VerifyTicketRefreshed',
            'Overtrue\LaravelWeChat\Events\OpenPlatformSubscriber@VerifyTicketRefreshed'
        );
    }

}
