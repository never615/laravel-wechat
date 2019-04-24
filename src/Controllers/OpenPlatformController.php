<?php

/*
 * This file is part of the overtrue/laravel-wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\LaravelWeChat\Controllers;

use EasyWeChat\OpenPlatform\Application;
use EasyWeChat\OpenPlatform\Server\Guard;
use Illuminate\Support\Facades\Event;
use Overtrue\LaravelWeChat\Events\OpenPlatform as Events;

class OpenPlatformController extends Controller
{
    /**
     * Register for open platform.
     *
     * @param \EasyWeChat\OpenPlatform\Application $application
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \EasyWeChat\Kernel\Exceptions\BadRequestException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function server(Application $application)
    {
//        \Log::info('request arrived.'); # 注意：Log 为 Laravel 组件，所以它记的日志去 Laravel 日志看，而不是 EasyWeChat 日志
//        \Log::info(Input::all());
//        \Log::info(file_get_contents("php://input"));

        $server = $application->server;

        $server->on(Guard::EVENT_AUTHORIZED, function ($payload) {
            Event::fire(new Events\Authorized($payload));
        });
        $server->on(Guard::EVENT_UNAUTHORIZED, function ($payload) {
            Event::fire(new Events\Unauthorized($payload));
        });
        $server->on(Guard::EVENT_UPDATE_AUTHORIZED, function ($payload) {
            Event::fire(new Events\UpdateAuthorized($payload));
        });
        $server->on(Guard::EVENT_COMPONENT_VERIFY_TICKET, function ($payload) {
            Event::fire(new Events\VerifyTicketRefreshed($payload));
        });

        return $server->serve();
    }
}
