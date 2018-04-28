<?php

namespace Overtrue\LaravelWeChat\Controllers\CorpServer;


use EasyWeChat\Foundation\Application;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Mallto\Admin\Data\Administrator;
use Mallto\Admin\Data\Permission;
use Mallto\Admin\Data\Role;
use Mallto\Admin\Data\Subject;
use Mallto\Dangjian\Data\RegisterVerifyInfo;
use Mallto\Tool\Exception\PermissionDeniedException;
use Overtrue\LaravelWeChat\Model\WechatCorpAuth;
use Overtrue\LaravelWeChat\Model\WechatCorpAuthRepository;
use Overtrue\LaravelWeChat\WechatUtils;
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
//        Log::info("--------- loginFromCorp -----------");
        $input = Input::all();
        if (count($input) == 0) {
            return;
        }
        $userInfo = $this->corp_server_qa->login_user->getUserInfo();
//        Log::info($userInfo);

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

        $subject = Subject::where("uuid", $corpId)->first();

        if (!$subject) {
            throw new PermissionDeniedException("企业号对应项目主体不存在,请联系开发商.");
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
        $admin = $this->createAdmin($subject, $userInfo, $agentIds, $wechatCorpAuth);

        //分配角色
        if ($agentIds == "all") {
            //配置主体总管理角色
            $this->adminRole($subject, $admin);

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
            throw new PermissionDeniedException("权限不足,该分级管理员没有设置任何应用权限");
        }


        if (Auth::guard('admin')->attempt([
            'username' => $admin->username,
            'password' => $admin->username,
        ])
        ) {
            admin_toastr(trans('admin.login_successful'));

            return redirect(config('admin.route.prefix'));
        }

        Log::info("跳转到服务商管理端失败".$this->getFailedLoginMessage());

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
     * @param $agentIds,账号可以查看的应用范围
     * @param $wechatCorpAuth
     * @return Administrator
     */
    private function createAdmin($subject, $userInfo, $agentIds, $wechatCorpAuth)
    {
        $name = "创建者";


        if (isset($userInfo["user_info"]['userid'])) {
            $username = $userInfo["user_info"]['userid'];
            if (isset($userInfo["user_info"]['name'])) {
                $name = $userInfo["user_info"]['name'];
            }
        } else {
            \Log::error("获取不到用户标识用做管理端登录名");
            throw new PermissionDeniedException("权限不足,无法获取用户标识");
        }




        //去注册信息表查改用户对应的主体id,即:所属党支部
        $registerInfo = null;
        if (isset($userInfo["user_info"]['userid'])) {

            $qyUserid = $userInfo["user_info"]['userid'];

            $registerInfo = RegisterVerifyInfo::where("qy_userid", $qyUserid)
                ->where("top_subject_id", $subject->id)
                ->first();
        }


        if ($agentIds == 'all') {
            //超级管理员,管理端的账号所属主体为中航
            $tempSubjectId = $subject->id;
        } else {
            //非超级管理员,目前只有分级管理员,如果有注册核对信息,则分配账号所属主体为具体主体
            //即只拥有对应主体的数据查看范围,没有的话还是设置为中航的主体
            if ($registerInfo) {
                $tempSubjectId = $registerInfo->subject_id;
            }else{
                $tempSubjectId = $subject->id;
            }

        }

        //拥有党建应用权限的人,必须有注册核对信息,必须注册微信用户,否则就不能创建
        if ($this->hasAppPermission($agentIds, $wechatCorpAuth, 2)) {
            if (!$registerInfo || !$registerInfo->is_register) {
                throw new PermissionDeniedException("未在先锋课堂注册,无法进入后台");
            }
        }


        //如果已经是管理员,但是权限改变,如:是问答分级管理员或者超级管理员->党建管理员

        //admin不存在,也有可能是已经是管理员了,但是所属主体变了,需要处理
        //不需要管,结果就是一个人可能会有多个账号,但是旧的用不到了


        $admin = Administrator::where("username", $username.'_'.$subject->id)->first();

//        //-------------------- 升级临时代码: -----------
//        $admin = Administrator::where("username", $username.'_'.$tempSubjectId)
//            ->where('subject_id', $tempSubjectId)->first();
//
//        if ($admin) {
//            $admin->username = $username.'_'.$subject->id;
//            $admin->password = bcrypt($username.'_'.$subject->id);
//            $admin->save();
////            $admin=Administrator::where("username", $username.'_'.$subject->id)->first();
//        } else {
//            $admin = Administrator::where("username", $username.'_'.$subject->id)->first();
//        }
//
//        //-------------------- 升级临时代码 -----------


        if (!$admin) {
            $extra = null;
            if (isset($userInfo["user_info"]['userid'])) {
                $extra = ["qy_userid" => $userInfo["user_info"]['userid']];
            }

            $admin = Administrator::create([
                'username'       => $username.'_'.$subject->id,
                'password'       => bcrypt($username.'_'.$subject->id),
                'name'           => $name,
                "subject_id"     => $tempSubjectId,
                "adminable_id"   => $tempSubjectId,
                "adminable_type" => "subject",
                'extra'          => $extra,
            ]);
            if ($agentIds == 'all') {
                //超级管理员分配全部数据查看范围
                $admin->manager_subject_ids = ["$subject->id"];
                $admin->save();
            }
        } else {
            $tempExtra = $admin->extra;
            if (isset($userInfo["user_info"]['userid'])) {
                $tempExtra['qy_userid'] = $userInfo["user_info"]['userid'];
            }

            $admin->extra = $tempExtra;


            if ($agentIds == 'all') {
                //超级管理员分配全部数据查看范围
                $admin->manager_subject_ids = ["$subject->id"];
            } else {
//                $admin->manager_subject_ids = null;
            }
            $admin->save();
        }

        return $admin;
    }


    /**
     * 不是超级管理员,且拥有指定的appid权限,则返回true
     *
     * @param $agentIds
     * @param $wechatCorpAuth
     * @param $needCheckAppId ,1是问答应用;2是党校应用
     * @return bool
     * @internal param $agentId
     */
    private function hasAppPermission($agentIds, $wechatCorpAuth, $needCheckAppId)
    {
        if (!is_array($agentIds)) {
            return false;
        }
        foreach ($agentIds as $agentId) {
            $authInfo = $wechatCorpAuth->auth_info;
            $authInfo = $authInfo['auth_info'];
            $agents = $authInfo["agent"];
            $appId = null;
            foreach ($agents as $agent) {
                if ($agent["agentid"] == $agentId) {
                    //找到
                    $appId = $agent["appid"];
                }
            }

            return $appId == $needCheckAppId;
        }

        return false;
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
                    $this->djRole($subject, $admin, true);
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
     * 只分配查看权限,用户相关的,还是有统计
     *
     * @param      $subject
     * @param      $admin
     * @param bool $isSubAdmin
     */
    private function djRole($subject, $admin, $isSubAdmin = false)
    {
        if ($isSubAdmin) {
            //分配查看角色
            //分配党建管理员角色
            $viewRole = Role::where("slug", "dangxiao_user_view")
                ->where("subject_id", $subject->id)
                ->first();

            if (!$viewRole) {
                $viewRole = Role::create([
                    "name"       => "e党校用户相关查看管理员",
                    "slug"       => "dangxiao_user_view",
                    "subject_id" => $subject->id,
                ]);


                $userCoursePermission = Permission::where("slug", "user_courses")->first();
                $userExamPermission = Permission::where("slug", "user_exams")->first();
                $userStudyPermission = Permission::where("slug", "user_online_studies")->first();
                $userPermission = Permission::where("slug", "users")->first();
                $studyTimePermission = Permission::where("slug", "user-study-time-records")->first();
                $statisticsPermission = Permission::where("slug", "dj_subject_statistics")->first();
                $djConfigPermission = Permission::where("slug", "dj_configs")->first();

                $viewRole->permissions()->save($userCoursePermission);
                $viewRole->permissions()->save($userExamPermission);
                $viewRole->permissions()->save($userStudyPermission);
                $viewRole->permissions()->save($userPermission);
                $viewRole->permissions()->save($studyTimePermission);
                $viewRole->permissions()->save($statisticsPermission);
                $viewRole->permissions()->save($djConfigPermission);
            }

            $tempRole = $admin->roles()->where("slug", $viewRole->slug)->first();
            if (!$tempRole) {
                $admin->roles()->save($viewRole);
            }
        } else {
            //分配党建管理员角色
            $role = Role::where("slug", "dangxiao")
                ->where("subject_id", $subject->id)
                ->first();

            if (!$role) {
                $role = Role::create([
                    "name"       => "e党校管理员",
                    "slug"       => "dangxiao",
                    "subject_id" => $subject->id,
                ]);

                $companyPermission = Permission::where("slug", "companies")->first();
                $partyTagPermission = Permission::where("slug", "party_tags")->first();
                $verifyInfoPermission = Permission::where("slug", "verify_user_infos")->first();
                $userPermission = Permission::where("slug", "users")->first();
                $coursePermission = Permission::where("slug", "course_parent")->first();
                $examPermission = Permission::where("slug", "exam_parent")->first();
                $studyPermission = Permission::where("slug", "online_study_parent")->first();
                $studyTimePermission = Permission::where("slug", "user-study-time-records")->first();
                $statisticsPermission = Permission::where("slug", "dj_subject_statistics")->first();
                $djConfigPermission = Permission::where("slug", "dj_configs")->first();


                $role->permissions()->save($coursePermission);
                $role->permissions()->save($examPermission);
                $role->permissions()->save($studyPermission);
                $role->permissions()->save($companyPermission);
                $role->permissions()->save($verifyInfoPermission);
                $role->permissions()->save($partyTagPermission);
                $role->permissions()->save($userPermission);
                $role->permissions()->save($studyTimePermission);
                $role->permissions()->save($statisticsPermission);
                $role->permissions()->save($djConfigPermission);
            }
            $tempRole = $admin->roles()->where("slug", $role->slug)->first();
            if (!$tempRole) {
                $admin->roles()->save($role);
            }
        }


    }


    /**
     * 分配管理员角色
     *
     * @param $subject
     * @param $admin
     */
    private function adminRole($subject, $admin)
    {
        //检查该主体是否有总管理角色,没有则创建
        $adminRole = Role::where("slug", 'admin')
            ->where('subject_id', $subject->id)
            ->first();

        if (!$adminRole) {
            $adminRole = Role::create([
                "subject_id" => $subject->id,
                "slug"       => 'admin',
                "name"       => $subject->name."总管理员",
            ]);


            //分配主体基本权限
            $subjectPermission = Permission::where("slug", "subjects")->first();
            $adminPermission = Permission::where("slug", "admins")->first();
            $rolePermission = Permission::where("slug", "roles")->first();
            $reportPermission = Permission::where("slug", "reports")->first();
            $userPermission = Permission::where("slug", "users")->first();
            $companyPermission = Permission::where("slug", "companies")->first();
            $verifyInfoPermission = Permission::where("slug", "verify_user_infos")->first();
            $partyTagPermission = Permission::where("slug", "party_tags")->first();


            $adminRole->permissions()->save($subjectPermission);
            $adminRole->permissions()->save($adminPermission);
            $adminRole->permissions()->save($rolePermission);
            $adminRole->permissions()->save($reportPermission);

            $adminRole->permissions()->save($userPermission);
            $adminRole->permissions()->save($companyPermission);
            $adminRole->permissions()->save($verifyInfoPermission);
            $adminRole->permissions()->save($partyTagPermission);

        }

        $tempRole = $admin->roles()->where("slug", $adminRole->slug)->first();
        if (!$tempRole) {
            $admin->roles()->save($adminRole);
        }


    }


}
