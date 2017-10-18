<?php

namespace Overtrue\LaravelWechat\Controllers\CorpServer;


use EasyWeChat\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Overtrue\LaravelWechat\Model\WechatCorpAuth;
use Overtrue\LaravelWechat\Model\WechatCorpAuthRepository;
use Overtrue\LaravelWechat\WechatUtils;

/**
 * 通讯录套件
 * Created by PhpStorm.
 * User: never615
 * Date: 11/04/2017
 * Time: 7:15 PM
 */
class ContactCorpController extends Controller
{

    private $wechat;
    private $corp_server_contact;
    /**
     * @var WechatUtils
     */
    private $wechatUtils;
    /**
     * @var WechatCorpAuthRepository
     */
    private $wechatCorpAuthRepository;


    /**
     * WechatOpenPlatformController constructor.
     *
     * @param Application              $wechat
     * @param WechatUtils              $wechatUtils
     * @param WechatCorpAuthRepository $wechatCorpAuthRepository
     */
    public function __construct(
        Application $wechat,
        WechatUtils $wechatUtils,
        WechatCorpAuthRepository $wechatCorpAuthRepository
    ) {
        $this->wechat = $wechat;
        $this->corp_server_contact = $wechat->corp_server_contact;
        $this->wechatUtils = $wechatUtils;
        $this->wechatCorpAuthRepository = $wechatCorpAuthRepository;
    }


    /**
     * 处理微信的请求消息
     *
     * @param Request $request
     * @return string
     */
    public function serve(Request $request)
    {
//        Log::info('request arrived.'); # 注意：Log 为 Laravel 组件，所以它记的日志去 Laravel 日志看，而不是 EasyWeChat 日志
//        Log::info(Input::all());
//        Log::info($request->getContent(false));

        //todo 事件处理

        // 自定义处理
        $this->corp_server_contact->server->setMessageHandler(function ($event) use ($request) {
            // 事件类型常量定义在 \EasyWeChat\OpenPlatform\Guard 类里
//            Log::info($event);

            switch ($event->InfoType) {
                case 'create_auth':
                    Log::info("create_auth");
                    $authorizationInfo = $this->corp_server_contact->
                    getAuthorizationInfo($event['AuthCode']);

                    $this->authSuccess($authorizationInfo['permanent_code'],
                        $authorizationInfo["auth_corp_info"]["corpid"]);
                    break;
                case 'change_auth':
                    Log::info("change_auth");
                    $this->authSuccess(null, $event->AuthCorpId);
                    break;
                case 'cancle_auth':
                    Log::info("cancle_auth");
                    Log::info($event);
                    break;
                case 'suite_ticket':
                    // ...
//                    Log::info("suite_ticket");
                    break;
                default:
//                    Log::info("其他事件");
//                    Log::info($event);
//                    Log::info($request->getContent(false));
                    break;
            }
        });


        return $this->corp_server_contact->server->serve();
    }

    /**
     * 第三方公众号请求授权
     */
    public function auth()
    {
        return $this->corp_server_contact->pre_auth
            ->redirect(\Request::root().'/wechat/corp_server/contact/auth/callback');
    }

    /**
     * 第三方公众号请求授权的回调
     */
    public function authCallback()
    {
        \Log::info("----------- 授权回调authCallback ------");
//        // 使用授权码换取公众号的接口调用凭据和授权信息
//        // Optional: $authorizationCode 不传值时会自动获取 URL 中 auth_code 值
        $authorizationInfo = $this->corp_server_contact->getAuthorizationInfo();
        $this->authSuccess($authorizationInfo['permanent_code'],
            $authorizationInfo["auth_corp_info"]["corpid"]);

//        echo $authorizationInfo['auth_corp_info']['corp_name']."申请使用深圳墨兔企业号应用(问答系统)成功";

        return view("admin::login");
    }

//    /**
//     * js签名
//     *
//     * @param \Symfony\Component\HttpFoundation\Request $request
//     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
//     */
//    public function jsConfig(\Symfony\Component\HttpFoundation\Request $request)
//    {
//        list($corpId, $permanentCode) = $this->wechatUtils->createAuthorizerApplicationParamsByCorp($request);
//        $corp_server_contact = $this->wechat->corp_server_contact;
//        $app = $corp_server_contact->createAuthorizerApplication($corpId, $permanentCode);
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

    /**
     * 授权之后的处理:一种是回调拿到authCode,一种是服务商网站授权拿到authCode
     *
     * @param $permanentCode
     * @param $corpId
     * @internal param $authorizationInfo
     */
    private function authSuccess($permanentCode, $corpId)
    {
        Log::info("------------ authHandler ---------");

        $wechatCorpAuth = WechatCorpAuth::where("corp_id", $corpId)
            ->first();

        if (empty($permanentCode) && $wechatCorpAuth) {
            $permanentCode = $wechatCorpAuth->permanent_code;
        }

        //获取授权方的公众号帐号基本信息
        $authorizerInfo = $this->corp_server_contact
            ->getAuthorizerInfo($corpId,
                $permanentCode);

        Log::info($authorizerInfo);
//        $data = [
//            'corp_id'        => $corpId,
//            'permanent_code' => $permanentCode,
//            'corp_name'      => $authorizerInfo['auth_corp_info']['corp_name'],
//            'auth_info'      => $authorizerInfo,
//            'uuid'           => $corpId,
//        ];
//        if ($wechatCorpAuth) {
//            call_user_func([$wechatCorpAuth, "update"], $data);
//        } else {
//            WechatCorpAuth::create($data);
//        }
//
//        //如果授权了appId为1的应用,即问答应用,则创建菜单
//        $agents = $authorizerInfo['auth_info']['agent'];
//        foreach ($agents as $agent) {
//            switch ($agent["appid"]) {
//                case 1: //问答应用
//                    $this->generateQaMenu($corpId, $permanentCode);
//                    break;
//                case 2: //党建应用
//
//                    break;
//                default:
//                    \Log::info("其他应用:".$agent["appid"]);
//                    break;
//            }
//        }
    }


    /**
     * 生成问答菜单
     *
     * @param $corpId
     * @param $permanentCode
     */
    private function generateQaMenu($corpId, $permanentCode)
    {
        //授权处理自动生成菜单
        $app = $this->corp_server_contact->createAuthorizerApplication($corpId, $permanentCode);
        $agentId = $this->wechatCorpAuthRepository->getAgentId($corpId, 1);

        $env = config("app.debug") ? "staging" : "production";
        $app->menu->add([
            [
                "type" => "view",
                "name" => "微问答",
//                "url"  => "https://qy.mall-to.com/wechat/qa/user?uuid=".$corpId,
                "url"  => "https://qy.mall-to.com/wechat_page/$env/avic/index.html?uuid=".$corpId,
            ],
        ], $agentId);
    }
}
