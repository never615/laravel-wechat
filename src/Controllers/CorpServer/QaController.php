<?php
namespace Overtrue\LaravelWechat\Controllers\CorpServer;


use EasyWeChat\Foundation\Application;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Overtrue\LaravelWechat\Model\WechatCorpAuth;
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

        Log::info("----------- 授权回调 ------");


//        $this->corp_server_qa->getAuthorizerInfo();

//        // 使用授权码换取公众号的接口调用凭据和授权信息
//        // Optional: $authorizationCode 不传值时会自动获取 URL 中 auth_code 值
        $authorizationInfo = $this->corp_server_qa->getAuthorizationInfo();
        Log::info($authorizationInfo);

//        //获取授权方的公众号帐号基本信息
        $authorizerInfo = $this->corp_server_qa->getAuthorizerInfo($authorizationInfo["auth_corp_info"]["corpid"],$authorizationInfo['permanent_code']);
        Log::info($authorizerInfo);


        $corpId=$authorizationInfo['auth_corp_info']['corpid'];

        $wechatCorpAuth=WechatCorpAuth::where("corp_id",$corpId)->first();


        $data=[
            'corp_id'=>$corpId,
            'permanent_code'=>$authorizationInfo['permanent_code'],
            'corp_name'=>$authorizationInfo['auth_corp_info']['corp_name'],
            'auth_info'=>$authorizationInfo
        ];
        if ($wechatCorpAuth) {
            call_user_func([$wechatCorpAuth, "update"], $data);
        } else {
            WechatCorpAuth::create($data);
        }

        echo $authorizationInfo['auth_corp_info']['corp_name']."申请使用墨兔科技企业号应用(问答系统)成功";

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
