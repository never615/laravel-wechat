<?php

namespace Overtrue\LaravelWechat\Controllers\CorpServer;


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
use Mallto\Dangjian\Data\RegisterVerifyInfo;
use Mallto\Tool\Exception\PermissionDeniedException;
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
class CorpAppController extends Controller
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
        try {


            $permanentCode = "";
            $authInfo = WechatCorpAuth::where('corp_id', $corpId)->first();
            if ($authInfo) {
                $permanentCode = $authInfo->permanent_code;
            }
            $app = $this->corp_server_qa->createAuthorizerApplication($corpId, $permanentCode);

            return $app->server->serve();
        } catch (\Exception $e) {

        }
    }

    /**
     * 业务设置URL
     *
     * 从企业号登录一键登录到服务商网站
     *
     * 业务设置URL:该URL为服务商侧的管理后台链接,
     * 授权企业的管理员可从企业号后台的应用详情页免登录直接跳转该链接
     */
    public function loginFromCorp(Request $request)
    {
        Log::info("--------- loginFromCorp -----------");
        $input = Input::all();
        if (count($input) == 0) {
            return;
        }
        $userInfo = $this->corp_server_qa->login_user->getUserInfo();
        Log::info($userInfo);

        /*
         *
        分区管理员示例:

        {
          "usertype": 4,
          "user_info": {
            "userid": "HUGOHO",
            "name": "HUGOHO",
            "avatar": "http://p.qlogo.cn/bizmail/lhKpScqMdMGdic1wFeLICok68n7tbyWhELC33iaOGDrCCxDxmXU7JNVA/0"
          },
          "corp_info": {
            "corpid": "wx6223037d8b9390be"
          },
          "agent": [
            {
              "agentid": 1000006,
              "auth_type": 1
            }
          ],
          "auth_info": {
            "department": [
              {
                "id": 1,
                "writable": true
              }
            ]
          }
        }
         */


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

        //管理员拥有的应用权限
        $agentIds = null;

        switch ($userInfo["usertype"]) {
            case "1": //创建者
                $agentIds = 'all';
                break;
            case "2": //内部管理员
                $agentIds = 'all';
                break;
            case "3": //服务商管理员
                throw new PermissionDeniedException("暂不支持服务商管理员登录");
                break;
            case "4": //分区管理员
                //检查分区管理员拥有的应用权限,然后分配相应的角色给他
                foreach ($userInfo["agent"] as $item) {
                    $agentIds[] = $item["agentid"];
                }
                break;
            default:
                throw new PermissionDeniedException("权限不足");
                break;
        }

        //创建管理端账户
        $admin = $this->createAdmin($subject, $userInfo);
        //分配角色
        if ($agentIds == "all") {
            //分配已有所有角色
            $this->qaRole($subject, $admin);
            $this->djRole($subject, $admin);
        } elseif (is_array($agentIds)) {
            foreach ($agentIds as $agentId) {
                $this->roleByAgent($agentId, $wechatCorpAuth, $admin, $subject);
            }
        } else {
            //不分配任何角色
            \Log::error("不分配任何角色");
            throw new PermissionDeniedException("权限不足");
        }

        if (Auth::guard('admin')->attempt([
            'username' => $admin->username,
            'password' => $admin->username,
        ])
        ) {
            admin_toastr(trans('admin::lang.login_successful'));

            return redirect(config('admin.prefix'));
        }

        Log::info("跳转到服务商管理端失败");

        return Redirect::back()->withInput()->withErrors(['username' => $this->getFailedLoginMessage()]);

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


    /**
     * 创建管理端账户
     *
     * @param $subject
     * @param $userInfo
     * @return $this|\Illuminate\Database\Eloquent\Model|null|static
     */
    private function createAdmin($subject, $userInfo)
    {
        $name = "创建者";

        if (isset($userInfo["user_info"]['email'])) {
            $username = $userInfo["user_info"]['email'];
            if (isset($userInfo["user_info"]['name'])) {
                $name = $userInfo["user_info"]['name'];
            } else {
                $name = $userInfo["user_info"]['email'];
            }
        } elseif (isset($userInfo["user_info"]['userid'])) {
            $username = $userInfo["user_info"]['userid'];
            if (isset($userInfo["user_info"]['name'])) {
                $name = $userInfo["user_info"]['name'];
            }
        } else {
            \Log::error("获取不到用户标识用做管理端登录名");
            throw new PermissionDeniedException("权限不足");
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
                'extra'          => ["qy_userid" => $userInfo["user_info"]['userid']],
            ]);
        } else {
            $tempExtra = $admin->extra;
            $tempExtra['qy_userid'] = $userInfo["user_info"]['userid'];
            $admin->extra = $tempExtra;
            $admin->save();
        }

        return $admin;
    }

    /**
     * 根据agentId分配角色
     *
     * @param $agentId
     */
    private function roleByAgent($agentId, $wechatCorpAuth, $admin, $subject)
    {
        //根据agentid分配角色
//        \Log::info("根据agentid分配角色".$agentId);

        //拿着agentid查询对应的appid,然后确认是哪个应用
        $authInfo = $wechatCorpAuth->auth_info;
//        \Log::info($authInfo);
        $authInfo = $authInfo['auth_info'];
        $agents = $authInfo["agent"];
        $appId = null;
        foreach ($agents as $agent) {
            if ($agent["agentid"] == $agentId) {
                //找到
                $appId = $agent["appid"];
            }
        }

        if (!empty($appId)) {
            switch ($appId) {
                case 1: //问答应用
                    $this->qaRole($subject, $admin);
                    break;
                case 2: //党建应用
                    $this->djRole($subject, $admin,true);
                    break;
                default:
                    break;
            }
        }
    }


    /**
     * 给管理端用户分配问答管理角色
     *
     * @param $subject
     * @param $admin
     */
    private function qaRole($subject, $admin)
    {
        //分配问答管理员角色
        $role = Role::where("slug", "qa")
            ->where("subject_id", $subject->id)
            ->first();

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
    }


    /**
     * 给管理端用户分配党建管理角色
     *
     * @param      $subject
     * @param      $admin
     * @param bool $isSubAdmin
     */
    private function djRole($subject, $admin, $isSubAdmin = false)
    {
        //分配问答管理员角色
        $role = Role::where("slug", "dangjian")
            ->where("subject_id", $subject->id)
            ->first();

        if (!$role) {
            if ($isSubAdmin) {

                //去注册信息表查改用户对应的主体id,即:所属党支部
                $qyUserid = $admin->extra['qy_userid'];

                $registerInfo = RegisterVerifyInfo::where("qy_userid", $qyUserid)
                    ->where("temp_subject_id", $subject->id)
                    ->first();

                if ($registerInfo) {
                    throw new PermissionDeniedException("请先在微信端注册党员信息,才可登录管理端");
                }

                $role = Role::create([
                    "name"       => "党建管理员",
                    "slug"       => "dangjian",
                    "subject_id" => $registerInfo->subject_id,
                ]);
            } else {
                $role = Role::create([
                    "name"       => "党建管理员",
                    "slug"       => "dangjian",
                    "subject_id" => $subject->id,
                ]);

            }


            $coursePermission = Permission::where("slug", "course_parent")->first();
            $examPermission = Permission::where("slug", "exam_parent")->first();
            $studyPermission = Permission::where("slug", "online_study_parent")->first();
            $companyPermission = Permission::where("slug", "companies")->first();
            $verifyInfoPermission = Permission::where("slug", "verify_user_infos")->first();
            $partyTagPermission = Permission::where("slug", "party_tags")->first();
            $videosPermission = Permission::where("slug", "videos")->first();

            $role->permissions()->save($coursePermission);
            $role->permissions()->save($examPermission);
            $role->permissions()->save($studyPermission);
            $role->permissions()->save($companyPermission);
            $role->permissions()->save($verifyInfoPermission);
            $role->permissions()->save($partyTagPermission);
            $role->permissions()->save($videosPermission);
        }

        $tempRole = $admin->roles()->where("slug", $role->slug)->first();
        if (!$tempRole) {
            $admin->roles()->save($role);
        }
    }


}
