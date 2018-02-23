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

use Overtrue\LaravelWechat\Model\WechatUserInfoRepository;

class WeChatUserAuthorizedNotification
{
    /**
     * @var WechatUserInfoRepository
     */
    private $wechatUserInfoRepository;

    /**
     * 创建事件监听器.
     *
     */
    public function __construct(WechatUserInfoRepository $wechatUserInfoRepository)
    {
        //
        $this->wechatUserInfoRepository = $wechatUserInfoRepository;
    }

    /**
     * 处理事件.
     *
     * @return void
     */
    public function handle($event)
    {
        // 该事件有以下属性
//        $event->user; // 同 session('wechat.oauth_user.default') 一样
//        $event->isNewSession; // 是不是新的会话（第一次创建 session 时为 true）
//        $event->account; // 当前中间件所使用的账号，对应在配置文件中的配置项名称
//        $event->officialAccount; // 当前中间件对应的公众号id,开放平台则是授权的第三方公众号的id



        if ($event->isNewSession) {
            $this->wechatUserInfoRepository->createOrUpdate($event->user, $event->officialAccount);
        }

    }
}
