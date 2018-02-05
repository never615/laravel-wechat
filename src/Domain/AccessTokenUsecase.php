<?php
/**
 * Created by PhpStorm.
 * User: never615 <never615.com>
 * Date: 02/11/2017
 * Time: 5:43 PM
 */

namespace Overtrue\LaravelWechat\Domain;


use EasyWeChat\Foundation\Application;
use Overtrue\LaravelWechat\Model\WechatAuthInfo;
use Overtrue\LaravelWechat\WechatUtils;

class AccessTokenUsecase
{
    /**
     * @var Application
     */
    private $wechat;
    /**
     * @var WechatUtils
     */
    private $wechatUtils;

    /**
     * Inject the wechat service.
     *
     * @param Application $wechat
     * @param WechatUtils $wechatUtils
     */
    public function __construct(
        Application $wechat,
        WechatUtils $wechatUtils
    ) {
        $this->wechat = $wechat;
        $this->wechatUtils = $wechatUtils;
    }


    public function refreshAccessToken()
    {
        \Log::warning('refreshAccessToken');

        $openPlatform = $this->wechat->open_platform;

        WechatAuthInfo::chunk(1, function ($auths) use ($openPlatform) {
            foreach ($auths as $auth) {
                $app = $openPlatform->createAuthorizerApplication($auth->authorizer_appid,
                    $auth->authorizer_refresh_token);
                $accessToken = $app->access_token;
                $token = $accessToken->getToken(true);
                \Log::warning("重新刷新token");
                \Log::warning($auth);
                \Log::warning($token);
            }
        });
    }
}
