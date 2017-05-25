<?php
namespace Overtrue\LaravelWechat\Controllers\CorpServer;


use App\Exceptions\PermissionDeniedException;
use EasyWeChat\Foundation\Application;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Auth\Database\Permission;
use Encore\Admin\Auth\Database\Role;
use Encore\Admin\Auth\Database\Subject;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Overtrue\LaravelWechat\Model\WechatCorpAuth;
use Overtrue\LaravelWechat\Model\WechatCorpAuthRepository;
use Overtrue\LaravelWechat\WechatUtils;
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
     * @var WechatUtils
     */
    private $wechatUtils;
    /**
     * @var WechatCorpAuthRepository
     */
    private $corpAuthRepository;

    /**
     * WechatOpenPlatformController constructor.
     *
     * @param Application              $wechat
     * @param WechatCorpAuthRepository $corpAuthRepository
     * @param WechatUtils              $wechatUtils
     */
    public function __construct(
        Application $wechat,
        WechatCorpAuthRepository $corpAuthRepository,
        WechatUtils $wechatUtils
    ) {
        $this->wechat = $wechat;
        $this->corp_server_qa = $wechat->corp_server_qa;
        $this->wechatUtils = $wechatUtils;
        $this->corpAuthRepository = $corpAuthRepository;
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

        //todo 事件处理

        // 自定义处理
        $this->corp_server_qa->server->setMessageHandler(function ($event) use ($request) {
            // 事件类型常量定义在 \EasyWeChat\OpenPlatform\Guard 类里
//            Log::info($event);

            switch ($event->InfoType) {
                case 'create_auth':
                    Log::info("create_auth");
                    $authorizationInfo = $this->corp_server_qa->getAuthorizationInfo($event['AuthCode']);
                    $this->authHandler($authorizationInfo);
                    break;
                case 'change_auth':
                    Log::info("change_auth");
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
                    Log::info("其他事件");
                    Log::info($event);
                    Log::info($request->getContent(false));


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
        Log::info("----------- 授权回调authCallback ------");
//        // 使用授权码换取公众号的接口调用凭据和授权信息
//        // Optional: $authorizationCode 不传值时会自动获取 URL 中 auth_code 值
        $authorizationInfo = $this->corp_server_qa->getAuthorizationInfo();
        $this->authHandler($authorizationInfo);
        //todo 进入应用管理页面
        echo $authorizationInfo['auth_corp_info']['corp_name']."申请使用深圳墨兔企业号应用(问答系统)成功";
    }

    /**
     * qa问答系统应用企业号用户消息的回调
     * callbackurl
     *
     * @param $corpId
     */
    public function corpCallback($corpId)
    {
//        Log::info("---------- coreCallback ---------");
//        $input = Input::all();
//        Log::info($input);
//
//        //todo 问答系统应用企业号用户消息的回调
//
//
//        $permanentCode = "";
//        $authInfo = WechatCorpAuth::where('corp_id', $corpId)->first();
//        if ($authInfo) {
//            $permanentCode = $authInfo->permanent_code;
//        }
//
//        $app = $this->corp_server_qa->createAuthorizerApplication($corpId, $permanentCode);
//
//        return $app->server->serve();
    }

    /**
     * 从企业号登录一键登录到服务商网站
     *
     * 业务设置URL:该URL为服务商侧的管理后台链接,
     * 授权企业的管理员可从企业号后台的应用详情页免登录直接跳转该链接
     */
    public function loginFromCorp(Request $request)
    {
        Log::info("--------- loginFromCorp -----------");
        $input = Input::all();
        Log::info($input);

        $userInfo = $this->corp_server_qa->login_user->getUserInfo();
        Log::info($userInfo);


        $corpId = $userInfo["corp_info"]['corpid'];

        $wechatCorpAuth = WechatCorpAuth::where("corp_id", $corpId)->first();

        if (!$wechatCorpAuth) {
            throw new PermissionDeniedException("企业号未授权");
        }

        $corpName = $wechatCorpAuth->corp_name;

        $subject = Subject::where("uuid", $corpId)->first();

        if (!$subject) {
            $subject = Subject::create([
                'name'      => $corpName,
                "parent_id" => 1,
                'uuid'      => $corpId,
            ]);
        }


        $name = "创建者";

        if ($userInfo["usertype"] != "1" && $userInfo["usertype"] != "2") {
            throw new PermissionDeniedException("权限不足");
        }


        if (isset($userInfo["user_info"]['email'])) {
            $username = $userInfo["user_info"]['email'];
        } else {
            $username = $userInfo["user_info"]['userid'];
            $name = $userInfo["user_info"]['name'];
        }


        $admin = Administrator::where("username", $username)->first();
        if (!$admin) {
            $admin = Administrator::create([
                'username'       => $username,
                'password'       => bcrypt($username),
                'name'           => $name,
                "subject_id"     => $subject->id,
                "adminable_id"   => $subject->id,
                "adminable_type" => "subject",
            ]);
        }

        $role = Role::where("slug", "qa")->where("subject_id", $subject->id)->first();
        if (!$role) {
            $role = Role::create([
                "name"       => "问答系统管理员",
                "slug"       => "qa",
                "subject_id" => $subject->id,
            ]);

            $qaPermission = Permission::where("slug", "qa")->first();
            $bannerPermission = Permission::where("slug", "page_banners")->first();

            $role->permissions()->save($qaPermission);
            $role->permissions()->save($bannerPermission);
        }

        $tempRole = $admin->roles()->where("slug", $role->slug)->first();
        if (!$tempRole) {
            $admin->roles()->save($role);
        }

        //todo 优化代码
        if (Auth::guard('admin')->attempt([
            'username' => $admin->username,
            'password' => $admin->username,
        ])
        ) {
            admin_toastr(trans('admin::lang.login_successful'));
            \Log::info(config('admin.prefix'));

            return redirect(config('admin.prefix'));
        }

        Log::info("跳转到服务商管理端失败");

        return Redirect::back()->withInput()->withErrors(['username' => $this->getFailedLoginMessage()]);

    }


    /**
     * 授权之后的处理:一种是回调拿到authCode,一种是服务商网站授权拿到authCode
     *
     * @param $authorizationInfo
     */
    private function authHandler($authorizationInfo)
    {
        Log::info("------------ authHandler ---------");
        Log::info($authorizationInfo);
//        //获取授权方的公众号帐号基本信息
        $authorizerInfo = $this->corp_server_qa->getAuthorizerInfo($authorizationInfo["auth_corp_info"]["corpid"],
            $authorizationInfo['permanent_code']);
        Log::info($authorizerInfo);


        $corpId = $authorizationInfo['auth_corp_info']['corpid'];

        $wechatCorpAuth = WechatCorpAuth::where("corp_id", $corpId)->first();


        $data = [
            'corp_id'        => $corpId,
            'permanent_code' => $authorizationInfo['permanent_code'],
            'corp_name'      => $authorizationInfo['auth_corp_info']['corp_name'],
            'auth_info'      => $authorizationInfo,
        ];
        if ($wechatCorpAuth) {
            call_user_func([$wechatCorpAuth, "update"], $data);
        } else {
            WechatCorpAuth::create($data);
        }
    }

    /**
     * @return string|\Symfony\Component\Translation\TranslatorInterface
     */
    protected function getFailedLoginMessage()
    {
        //todo 优化,直接使用登录

        return Lang::has('auth.failed')
            ? trans('auth.failed')
            : 'These credentials do not match our records.';
    }

}
