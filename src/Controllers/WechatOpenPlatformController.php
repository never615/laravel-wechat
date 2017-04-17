<?php
namespace Overtrue\LaravelWechat\Controllers;


use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Overtrue\LaravelWechat\WechatAuthInfo;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 11/04/2017
 * Time: 7:15 PM
 */
class WechatOpenPlatformController extends \App\Http\Controllers\Controller
{
    /**
     * 处理微信的请求消息
     *
     * @return string
     */
    public function serve()
    {
//        Log::info('request arrived.'); # 注意：Log 为 Laravel 组件，所以它记的日志去 Laravel 日志看，而不是 EasyWeChat 日志
//        Log::info(Input::all());
//
        $wechat = app('wechat');
        $openPlatform = $wechat->open_platform;

        // 自定义处理
        $openPlatform->server->setMessageHandler(function ($event) {
            // 事件类型常量定义在 \EasyWeChat\OpenPlatform\Guard 类里
            switch ($event->InfoType) {
                case 'authorized':
                case 'updateauthorized':
                    // 授权信息，主要是 token 和授权域
                    $info1 = $event->authorization_info;
                    // 授权方信息，就是授权方公众号的信息了
                    $info2 = $event->authorizer_info;
                    Log::info($info1);
                    Log::info($info2);
                    break;
                case 'unauthorized':
                    // ...
                    Log::info("unauthorized");
                    Log::info($event);
                    break;
                case 'component_verify_ticket':
                    // ...
//                    Log::info("component_verify_ticket");
                    break;
                default:
                    Log::info("其他事件");
                    break;
            }
        });


        return $openPlatform->server->serve();
    }

    /**
     * 第三方公众号请求授权
     */
    public function auth()
    {
        $wechat = app('wechat');
        $openPlatform = $wechat->open_platform;

        $authLink = $openPlatform->pre_auth
            ->setRedirectUri(Request::root().'/wechat/platform/auth/callback')
            ->getAuthLink();

        echo "<a href='$authLink' style='font-size: 30px'>墨兔微信开放平台授权地址,请点击进行授权</a>";
    }

    /**
     * 第三方公众号请求授权的回调
     */
    public function authCallback()
    {
        $wechat = app('wechat');
        $openPlatform = $wechat->open_platform;
        //使用授权码换取公众号的接口调用凭据和授权信息
        $authorizer = $openPlatform->authorizer;
        // 使用授权码换取公众号的接口调用凭据和授权信息
        // Optional: $authorizationCode 不传值时会自动获取 URL 中 auth_code 值
        $authorizationInfo = $authorizer->getAuthorizationInfo();
        //获取授权方的公众号帐号基本信息
        $authorizerInfo = $authorizer->getAuthorizerInfo($authorizationInfo["authorization_info"]["authorizer_appid"]);

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
            call_user_func([$wechatAuthInfo, "create"], $data);
        }
        echo $authorizerInfo["authorizer_info"]["nick_name"]."授权给微信开放平台服务商墨兔科技成功";
    }




    /**
     * js签名
     */
    public function jsConfig()
    {
        //todo js签名
    }


}
