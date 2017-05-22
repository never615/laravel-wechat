<?php
namespace Overtrue\LaravelWechat\Controllers\CorpServer;


use EasyWeChat\Foundation\Application;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 11/04/2017
 * Time: 7:15 PM
 */
class QaController extends Controller
{

    private $wechat;
    private $corp_server_qa;

    /**
     * WechatOpenPlatformController constructor.
     */
    public function __construct(Application $wechat)
    {
        $this->wechat = $wechat;
//        Log::info($wechat->keys());
        $this->corp_server_qa = $wechat->corp_server_qa;
    }


    /**
     * 处理微信的请求消息
     *
     * @return string
     */
    public function serve(Request $request)
    {
//        Log::info('request arrived.'); # 注意：Log 为 Laravel 组件，所以它记的日志去 Laravel 日志看，而不是 EasyWeChat 日志
//        Log::info(Input::all());
//        Log::info($request->getContent(false));

        // 自定义处理
        $this->corp_server_qa->server->setMessageHandler(function ($event) {
            // 事件类型常量定义在 \EasyWeChat\OpenPlatform\Guard 类里
            switch ($event->InfoType) {
                case 'create_auth':
                case 'change_auth':
                    Log::info("create_auth和change_auth");
//                    // 授权信息，主要是 token 和授权域
//                    $info1 = $event->authorization_info;
//                    // 授权方信息，就是授权方公众号的信息了
//                    $info2 = $event->authorizer_info;
//                    Log::info($info1);
//                    Log::info($info2);
                    break;
                case 'cancle_auth':
                    Log::info("cancle_auth");
                    Log::info($event);
                    break;
                case 'suite_ticket':
                    // ...
                    Log::info("suite_ticket");
                    break;
                default:
                    Log::info("其他事件");
                    break;
            }
        });


        return $this->corp_server_qa->server->serve();
    }

    /**
     * 第三方公众号请求授权
     */
    public function auth()
    {
        return $this->corp_server_qa->pre_auth->redirect(\Request::root().'/wechat/corp_server/qa/auth/callback');
    }

    /**
     * 第三方公众号请求授权的回调
     */
    public function authCallback()
    {

        $state = Input::get("state");
        $info=json_decode(urldecode($state));

        Log::info("----------- 授权回调 ------");
        Log::info($info);

//        // 使用授权码换取公众号的接口调用凭据和授权信息
//        // Optional: $authorizationCode 不传值时会自动获取 URL 中 auth_code 值
//        $authorizationInfo = $this->corp_server_qa->getAuthorizationInfo();
//        //获取授权方的公众号帐号基本信息
//        $authorizerInfo = $this->corp_server_qa->getAuthorizerInfo($authorizationInfo["authorization_info"]["authorizer_appid"]);
//
//        $authorization_appid = $authorizationInfo['authorization_info']['authorizer_appid'];
//        $authorization_access_token = $authorizationInfo['authorization_info']['authorizer_access_token'];
//        $authorization_refresh_token = $authorizationInfo['authorization_info']['authorizer_refresh_token'];
//
//        $wechatAuthInfo = WechatAuthInfo::where('authorizer_appid', $authorization_appid)->first();
//
//        $data = [
//            'authorizer_appid'         => $authorization_appid,
//            'authorizer_access_token'  => $authorization_access_token,
//            'authorizer_refresh_token' => $authorization_refresh_token,
//            'nick_name'                => $authorizerInfo['authorizer_info']['nick_name'],
//            'service_type_info'        => json_encode($authorizerInfo['authorizer_info']['service_type_info']),
//            'verify_type_info'         => json_encode($authorizerInfo['authorizer_info']['verify_type_info']),
//            'user_name'                => $authorizerInfo['authorizer_info']['user_name'],
//            'principal_name'           => $authorizerInfo['authorizer_info']['principal_name'],
//            'business_info'            => json_encode($authorizerInfo['authorizer_info']['business_info']),
//            'alias'                    => $authorizerInfo['authorizer_info']['alias'],
//            'qrcode_url'               => $authorizerInfo['authorizer_info']['qrcode_url'],
//            'func_info'                => json_encode($authorizerInfo['authorization_info']['func_info']),
//        ];
//
//        if ($wechatAuthInfo) {
//            call_user_func([$wechatAuthInfo, "update"], $data);
//        } else {
//            WechatAuthInfo::create($data);
//        }
//        echo $authorizerInfo["authorizer_info"]["nick_name"]."授权给微信开放平台服务商墨兔科技成功";

        echo "授权成功";

        //todo 进入应用管理页面
    }
//
//
//    /**
//     * js签名
//     *
//     * @param \Symfony\Component\HttpFoundation\Request $request
//     * @return
//     */
//    public function jsConfig(\Symfony\Component\HttpFoundation\Request $request)
//    {
//        list($appId, $refreshToken) = WechatUtils::createAuthorizerApplicationParams($request);
//        // 传递 AuthorizerAppId 和 AuthorizerRefreshToken（注意不是 AuthorizerAccessToken）即可。
//        $app = $this->openPlatform->createAuthorizerApplication($appId, $refreshToken);
//        // 调用方式与普通调用一致。
//        $js = $app->js;
//        $url = Input::get("url");
//        if (is_null($url)) {
//            throw new ResourceException("url is null");
//        }
//        $js->setUrl($url);
//        $result = $js->config([
//            'menuItem:copyUr',
//            'hideOptionMenu',
//            'hideAllNonBaseMenuItem',
//            'hideMenuItems',
//            'showMenuItems',
//            'showAllNonBaseMenuItem',
//            'onMenuShareTimeline',
//            'onMenuShareAppMessage',
//            'onMenuShareQQ',
//            'onMenuShareWeibo',
//            'onMenuShareQZone',
//        ], $debug = false, $beta = false, $json = true);
//
//        return response($result);
//    }


}
