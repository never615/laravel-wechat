<?php
namespace Overtrue\LaravelWechat\Controllers;


use Mallto\Tool\Exception\ResourceException;
use EasyWeChat\Foundation\Application;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Overtrue\LaravelWechat\Model\WechatAuthInfo;
use Overtrue\LaravelWechat\WechatUtils;

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
     * WechatOpenPlatformController constructor.
     *
     * @param Application $wechat
     * @param WechatUtils $wechatUtils
     */
    public function __construct(Application $wechat,WechatUtils $wechatUtils)
    {
        $this->wechat = $wechat;
        $this->openPlatform = $wechat->open_platform;
        $this->wechatUtils = $wechatUtils;
    }


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
        // 自定义处理
        $this->openPlatform->server->setMessageHandler(function ($event) {
            // 事件类型常量定义在 \EasyWeChat\OpenPlatform\Guard 类里
            switch ($event->InfoType) {
                case 'authorized':
                case 'updateauthorized':
                    Log::info("authorized和updateauthorized");
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


        return $this->openPlatform->server->serve();
    }

    /**
     * 第三方公众号请求授权
     */
    public function auth()
    {
        return $this->openPlatform->pre_auth->redirect(Request::root().'/wechat/platform/auth/callback');
    }

    /**
     * 第三方公众号请求授权的回调
     */
    public function authCallback()
    {
        // 使用授权码换取公众号的接口调用凭据和授权信息
        // Optional: $authorizationCode 不传值时会自动获取 URL 中 auth_code 值
        $authorizationInfo = $this->wechat->open_platform->getAuthorizationInfo();
        //获取授权方的公众号帐号基本信息
        $authorizerInfo = $this->wechat->open_platform->getAuthorizerInfo($authorizationInfo["authorization_info"]["authorizer_appid"]);

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
     * js签名
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function jsConfig(\Symfony\Component\HttpFoundation\Request $request)
    {
        list($appId, $refreshToken) = $this->wechatUtils->createAuthorizerApplicationParams($request);
        // 传递 AuthorizerAppId 和 AuthorizerRefreshToken（注意不是 AuthorizerAccessToken）即可。
        $app = $this->openPlatform->createAuthorizerApplication($appId, $refreshToken);
        // 调用方式与普通调用一致。
        $js = $app->js;
        $url = Input::get("url");
        if (is_null($url)) {
            throw new ResourceException("url is null");
        }
        $js->setUrl($url);
        $result = $js->config([
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
