<?php

namespace Overtrue\LaravelWeChat\Controllers;


use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Mallto\Tool\Exception\ResourceException;
use Overtrue\LaravelWeChat\Domain\OpenPlatformAuthUsecase;
use Overtrue\LaravelWeChat\Model\WechatAuthInfo;
use Overtrue\LaravelWeChat\WechatUtils;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 11/04/2017
 * Time: 7:15 PM
 */
class WechatOpenPlatformController extends Controller
{

    private $wechat;
    private $openPlatform;
    /**
     * @var WechatUtils
     */
    private $wechatUtils;
    /**
     * @var OpenPlatformAuthUsecase
     */
    private $openPlatformAuthUsecase;

    /**
     * WechatOpenPlatformController constructor.
     *
     * @param WechatUtils             $wechatUtils
     * @param OpenPlatformAuthUsecase $openPlatformAuthUsecase
     */
    public function __construct(WechatUtils $wechatUtils, OpenPlatformAuthUsecase $openPlatformAuthUsecase)
    {
        $this->openPlatform = \EasyWeChat::openPlatform(); // 开放平台
        $this->wechatUtils = $wechatUtils;
        $this->openPlatformAuthUsecase = $openPlatformAuthUsecase;
    }

//
//    /**
//     * 处理微信的请求消息
//     *
//     * @return string
//     */
//    public function serve()
//    {
////        \Log::info('request arrived.'); # 注意：Log 为 Laravel 组件，所以它记的日志去 Laravel 日志看，而不是 EasyWeChat 日志
////        \Log::info(Input::all());
////
//        // 自定义处理
//        $this->openPlatform->server->setMessageHandler(function ($event) {
//            // 事件类型常量定义在 \EasyWeChat\OpenPlatform\Guard 类里
//            switch ($event->InfoType) {
//                case 'authorized':
//                case 'updateauthorized':
//                    \Log::info("authorized和updateauthorized");
//                    // 授权信息，主要是 token 和授权域
//                    $info1 = $event->authorization_info;
//                    // 授权方信息，就是授权方公众号的信息了
//                    $info2 = $event->authorizer_info;
//                    \Log::info($info1);
//                    \Log::info($info2);
//                    break;
//                case 'unauthorized':
//                    // ...
//                    \Log::info("unauthorized");
//                    \Log::info($event);
//                    break;
//                case 'component_verify_ticket':
//                    // ...
////                    Log::info("component_verify_ticket");
//                    break;
//                default:
//                    \Log::info("其他事件");
//                    break;
//            }
//        });
//
//
//        return $this->openPlatform->server->serve();
//    }

    /**
     * 第三方公众号请求授权
     * 获取用户授权页 URL
     */
    public function auth()
    {
        $url = $this->openPlatform->getPreAuthorizationUrl(Request::root().'/wechat/platform/auth/callback'); // 传入回调URI即可

        return redirect($url);
    }

    /**
     * 第三方公众号请求授权的回调
     *
     * 使用授权码换取接口调用凭据和授权信息
     */
    public function authCallback()
    {
//        \Log::info('authCallback');
        $authorizerInfo = $this->openPlatformAuthUsecase->createOrUpdateAuthInfo();
        echo $authorizerInfo["authorizer_info"]["nick_name"]."授权给微信开放平台服务商深圳墨兔成功";

        return;

        // 使用授权码换取公众号的接口调用凭据和授权信息
        // Optional: $authorizationCode 不传值时会自动获取 URL 中 auth_code 值
        $authorizationInfo = $this->openPlatform->handleAuthorize();
//        \Log::info($authorizationInfo);

        //获取授权方的公众号帐号基本信息
        $authorizerInfo = $this->openPlatform->getAuthorizer($authorizationInfo["authorization_info"]["authorizer_appid"]);
//        \Log::info($authorizerInfo);

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
        echo $authorizerInfo["authorizer_info"]["nick_name"]."授权给微信开放平台服务商深圳墨兔成功";
    }


    /**
     * 公众号事件回调通知
     *
     * @param $appid
     */
    public function wechatCallback($appid)
    {
//        \Log::info($appid);
//        \Log::info(Input::all());
//        \Log::debug(file_get_contents("php://input"));

    }


    /**
     * js签名
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function jsConfig(\Symfony\Component\HttpFoundation\Request $request)
    {
        list($appId, $refreshToken) = $this->wechatUtils->createAuthorizerApplicationParams($request);
        // 传递 AuthorizerAppId 和 AuthorizerRefreshToken（注意不是 AuthorizerAccessToken）即可。
        $officialAccount = $this->openPlatform->officialAccount($appId, $refreshToken);

        // 调用方式与普通调用一致。
        $js = $officialAccount->jssdk;
        $url = Input::get("url");
        if (is_null($url)) {
            throw new ResourceException("url is null");
        }
        $js->setUrl($url);

        $result = $js->buildConfig([
            'menuItem:copyUr',
            'hideOptionMenu',
            'hideAllNonBaseMenuItem',
            'hideMenuItems',
            'showMenuItems',
            'showAllNonBaseMenuItem',
            'onMenuShareTimeline',
            'onMenuShareAppMessage',
            'onMenuShareQQ',
            'onMenuShareWeibo',
            'onMenuShareQZone',
            'openLocation',
        ], $debug = false, $beta = false, $json = true);

        return response($result);
    }


}
