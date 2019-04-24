<?php
/**
 * Created by PhpStorm.
 * User: never615 <never615.com>
 * Date: 2019/4/24
 * Time: 4:51 PM
 */

namespace Overtrue\LaravelWeChat\Domain;

use Overtrue\LaravelWeChat\Model\WechatAuthInfo;
use Overtrue\LaravelWeChat\WechatUtils;

class OpenPlatformAuthUsecase
{

    private $openPlatform;
    /**
     * @var WechatUtils
     */
    private $wechatUtils;

    /**
     * WechatOpenPlatformController constructor.
     *
     * @param WechatUtils $wechatUtils
     */
    public function __construct(WechatUtils $wechatUtils)
    {
        $this->openPlatform = \EasyWeChat::openPlatform(); // 开放平台
        $this->wechatUtils = $wechatUtils;
    }

    /**
     * 创建或者更新第三方公众号的授权信息
     *
     * @param null $authCode
     * @return
     */
    public function createOrUpdateAuthInfo($authCode = null)
    {
        // 使用授权码换取公众号的接口调用凭据和授权信息
        // Optional: $authorizationCode 不传值时会自动获取 URL 中 auth_code 值
        $authorizationInfo = $this->openPlatform->handleAuthorize($authCode);
//        \Log::debug($authorizationInfo);

        //获取授权方的公众号帐号基本信息
        $authorizerInfo = $this->openPlatform->getAuthorizer($authorizationInfo["authorization_info"]["authorizer_appid"]);
//        \Log::debug($authorizerInfo);

        $authorization_appid = $authorizationInfo['authorization_info']['authorizer_appid'];
        $authorization_access_token = $authorizationInfo['authorization_info']['authorizer_access_token'];
        $authorization_refresh_token = $authorizationInfo['authorization_info']['authorizer_refresh_token'];

        $wechatAuthInfo = WechatAuthInfo::where('authorizer_appid', $authorization_appid)->first();

        $data = [
            'authorizer_appid'         => $authorization_appid,
            'authorizer_access_token'  => $authorization_access_token,
            'authorizer_refresh_token' => $authorization_refresh_token,
            'nick_name'                => $authorizerInfo['authorizer_info']['nick_name'],
            'service_type_info'        => json_encode($authorizerInfo['authorizer_info']['service_type_info']),
            'verify_type_info'         => json_encode($authorizerInfo['authorizer_info']['verify_type_info']),
            'user_name'                => $authorizerInfo['authorizer_info']['user_name'],
            'principal_name'           => $authorizerInfo['authorizer_info']['principal_name'],
            'business_info'            => json_encode($authorizerInfo['authorizer_info']['business_info']),
            'alias'                    => $authorizerInfo['authorizer_info']['alias'],
            'qrcode_url'               => $authorizerInfo['authorizer_info']['qrcode_url'],
            'func_info'                => json_encode($authorizerInfo['authorization_info']['func_info']),
        ];

        if ($wechatAuthInfo) {
            call_user_func([$wechatAuthInfo, "update"], $data);
        } else {
            WechatAuthInfo::create($data);
        }

        return $authorizerInfo;
    }


    /**
     * 取消授权
     *
     * @param $authorizerAppid
     */
    public function unAuth($authorizerAppid)
    {
        WechatAuthInfo::where('authorizer_appid', $authorizerAppid)
            ->delete();
    }


}