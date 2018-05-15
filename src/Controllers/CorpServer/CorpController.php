<?php

namespace Overtrue\LaravelWeChat\Controllers\CorpServer;


use EasyWeChat\Foundation\Application;
use Encore\Admin\Auth\Database\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Mallto\Admin\Data\Subject;
use Mallto\Tool\Exception\ResourceException;
use Overtrue\LaravelWeChat\Model\WechatCorpAuth;
use Overtrue\LaravelWeChat\Model\WechatCorpAuthRepository;
use Overtrue\LaravelWeChat\WechatUtils;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 11/04/2017
 * Time: 7:15 PM
 */
class CorpController extends Controller
{

    private $wechat;
    private $corp_server_qa;
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
        $this->corp_server_qa = $wechat->corp_server_qa;
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
        $this->corp_server_qa->server->setMessageHandler(function ($event) use ($request) {
            // 事件类型常量定义在 \EasyWeChat\OpenPlatform\Guard 类里
//            Log::info($event);

            switch ($event->InfoType) {
                case 'create_auth':
                    Log::info("create_auth");
                    $authorizationInfo = $this->corp_server_qa->
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


        return $this->corp_server_qa->server->serve();
    }

    /**
     * 第三方公众号请求授权
     */
    public function auth()
    {
//        $url = \Request::root().'/wechat/corp_server/qa/auth/callback';

//        return '<a class="btn btn-primary" href="'.$url.'">授权成功,点击进入业务管理页面</a>';
        //RedirectResponse
        /**
         * @var RedirectResponse
         */
        $redirectResponse = $this->corp_server_qa->pre_auth->redirect(\Request::root().'/wechat/corp_server/qa/auth/callback');
        $url = $redirectResponse->getTargetUrl();

        return '<a class="btn btn-primary" href="'.$url.'">授权成功,点击进入业务管理页面</a>';

//        return $this->corp_server_qa->pre_auth->redirect(\Request::root().'/wechat/corp_server/qa/auth/callback');
    }

    /**
     * 第三方公众号请求授权的回调
     */
    public function authCallback()
    {
        \Log::info("----------- 授权回调authCallback ------");
//        // 使用授权码换取公众号的接口调用凭据和授权信息
//        // Optional: $authorizationCode 不传值时会自动获取 URL 中 auth_code 值
        $authorizationInfo = $this->corp_server_qa->getAuthorizationInfo();
        $this->authSuccess($authorizationInfo['permanent_code'],
            $authorizationInfo["auth_corp_info"]["corpid"]);

//        echo $authorizationInfo['auth_corp_info']['corp_name']."申请使用深圳墨兔企业号应用(问答系统)成功";

        return view("admin::login");
    }

    /**
     * js签名
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function jsConfig(\Symfony\Component\HttpFoundation\Request $request)
    {
        list($corpId, $permanentCode) = $this->wechatUtils->createAuthorizerApplicationParamsByCorp($request);
        $corp_server_qa = $this->wechat->corp_server_qa;
        $app = $corp_server_qa->createAuthorizerApplication($corpId, $permanentCode);
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
        ], $debug = false, $beta = false, $json = true);

        return response($result);
    }

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

        if (empty($permanentCode)) {
            //如果授权码不存在,则使用数据库保存的授权码
            if ($wechatCorpAuth) {
                $permanentCode = $wechatCorpAuth->permanent_code;
            }

            if (empty($permanentCode)) {
                \Log::warning("授权出错,授权码不存在");
                throw new ResourceException("授权出错,授权码不存在");
            }
        }

        //获取授权方的公众号帐号基本信息
        $authorizerInfo = $this->corp_server_qa
            ->getAuthorizerInfo($corpId, $permanentCode);

        $data = [
            'corp_id'        => $corpId,
            'permanent_code' => $permanentCode,
            'corp_name'      => $authorizerInfo['auth_corp_info']['corp_name'],
            'auth_info'      => $authorizerInfo,
            'uuid'           => $corpId,
        ];
        if ($wechatCorpAuth) {
            call_user_func([$wechatCorpAuth, "update"], $data);
        } else {
            WechatCorpAuth::create($data);
        }

        //创建主体,分配已购模块
        $subject = Subject::where("uuid", $corpId)->first();

        if (!$subject) {
            $subject = Subject::create([
                'name'      => $authorizerInfo['auth_corp_info']['corp_name'],
                "parent_id" => 1,
                'uuid'      => $corpId,
                "base"      => true,
            ]);
            //分配已购模块,根据agentId授权分配
        }


        $waitAddPermissionIds = [];

        //分配基础机构模块
        $basePermissionIds = Permission::whereIn("slug", [
            "users",
        ])
            ->pluck('id')
            ->toArray();

        $waitAddPermissionIds = array_merge($waitAddPermissionIds, $basePermissionIds);


        //如果授权了appId为1的应用,即问答应用,则创建菜单
        $agents = $authorizerInfo['auth_info']['agent'];
        foreach ($agents as $agent) {
            switch ($agent["appid"]) {
                case 1: //问答应用
//                    $this->generateQaMenu($corpId, $permanentCode);
                    //分配问答模块相关已购功能
                    $qaPermissionIds = Permission::whereIn("slug", [
                        'page',
                        "qa",
                    ])
                        ->pluck('id')
                        ->toArray();
                    $waitAddPermissionIds = array_merge($waitAddPermissionIds, $qaPermissionIds);
                    break;
                case 2: //党建应用
//                    $this->generateDjMenu($corpId, $permanentCode);
                    //分配党校模块相关已购功能
                    //和进行配置
                    //{"dangjian_statistics":1}

                    $djPermissionIds = Permission::whereIn("slug", [
                        'companies',
                        "party_tags",
                        'verify_user_infos',
                        'course_parent',
                        'exam_parent',
                        'online_study_parent',
                        'user-study-time-records',
                    ])
                        ->pluck('id')
                        ->toArray();

                    $waitAddPermissionIds = array_merge($waitAddPermissionIds, $djPermissionIds);


                    $subject->extra_config = '{"dangjian_statistics":1}';
                    $subject->save();

                    break;
                case 3://晒党建

                    $this->generateSdjMenu($corpId, $permanentCode);

                    $sdjPermissionIds = Permission::whereIn("slug", [
                        'sdj',
                    ])
                        ->pluck('id')
                        ->toArray();
                    $waitAddPermissionIds = array_merge($waitAddPermissionIds, $sdjPermissionIds);
                    break;
                default:
                    \Log::info("其他应用:".$agent["appid"]);
                    break;
            }
        }


        //检查已经有的权限,添加没有的
        $havedPermissionIds = $subject->permissions->pluck('id')->toArray();
        $newPermissionIds = array_diff($waitAddPermissionIds, $havedPermissionIds);

        $subject->permissions()->attach($newPermissionIds);
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
        $app = $this->corp_server_qa->createAuthorizerApplication($corpId, $permanentCode);
        $agentId = $this->wechatCorpAuthRepository->getAgentId($corpId, 1);

        $env = config('app.env');
        $url = config("app.url");
        $app->menu->add([
            [
                "type" => "view",
                "name" => "拜托了!伙伴",
                "url"  => "$url/wechat_page/$env/work/avic/index.html?uuid=$corpId&agent_id=$agentId",
            ],
        ], $agentId);
    }

    private function generateDjMenu($corpId, $permanentCode)
    {
        //授权处理自动生成菜单
        $app = $this->corp_server_qa->createAuthorizerApplication($corpId, $permanentCode);
        $agentId = $this->wechatCorpAuthRepository->getAgentId($corpId, 2);

        $env = config('app.env');
        $url = config("app.url");
        $app->menu->add([
            [
                "type" => "view",
                "name" => "e党校",
                "url"  => "$url/wechat_page/$env/work/learn/index.html?uuid=$corpId&agent_id=$agentId",
            ],
        ], $agentId);
    }


    private function generateSdjMenu($corpId, $permanentCode)
    {
        //授权处理自动生成菜单
        $app = $this->corp_server_qa->createAuthorizerApplication($corpId, $permanentCode);
        $agentId = $this->wechatCorpAuthRepository->getAgentId($corpId, 3);

        $env = config('app.env');
        $url = config("app.url");
        $app->menu->add([
            [
                "type" => "view",
                "name" => "晒党建",
                "url"  => "$url/wechat_page/$env/work/share/?uuid=$corpId&agent_id=$agentId",
            ],
        ], $agentId);
    }

}
//https://test-qy.mall-to.com/wechat_page/test/work/learn/index.html?uuid=$CORPID$&agent_id=$AGENTID$
