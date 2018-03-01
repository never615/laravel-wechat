<?php
/**
 * Created by PhpStorm.
 * User: never615 <never615.com>
 * Date: 02/11/2017
 * Time: 5:43 PM
 */

namespace Overtrue\LaravelWeChat\Domain;


use Overtrue\LaravelWeChat\Model\WechatAuthInfo;
use Overtrue\LaravelWeChat\WechatUtils;

class AccessTokenUsecase
{
    /**
     * @var WechatUtils
     */
    private $wechatUtils;

    /**
     * Inject the wechat service.
     *
     * @param WechatUtils $wechatUtils
     */
    public function __construct(
        WechatUtils $wechatUtils
    ) {
        $this->wechatUtils = $wechatUtils;
    }


    public function refreshAccessToken()
    {
//        \Log::warning('refreshAccessToken');
        $openPlatform = \EasyWeChat::openPlatform(); // 开放平台

        WechatAuthInfo::chunk(1, function ($auths) use ($openPlatform) {
            foreach ($auths as $auth) {
                $officialAccount = $openPlatform->officialAccount($auth->authorizer_appid,
                    $auth->authorizer_refresh_token);

                $accessToken = $officialAccount->access_token;
                try {
                    $accessToken->refresh();
//                    \Log::warning("重新刷新token");
//                    \Log::warning($auth);
                } catch (\Exception $exception) {
                    \Log::error("刷新token失败");
                    \Log::warning($exception->getMessage());
                    \Log::warning($exception->getTraceAsString());
                    \Log::warning($auth);
                }

            }
        });
    }
}
