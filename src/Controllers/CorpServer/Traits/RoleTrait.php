<?php

namespace Overtrue\LaravelWeChat\Controllers\CorpServer\Traits;

use Mallto\Admin\Data\Permission;
use Mallto\Admin\Data\Role;

/**
 * Created by PhpStorm.
 * User: never615 <never615.com>
 * Date: 2018/5/9
 * Time: 下午3:44
 */
trait RoleTrait
{
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
     * 晒党建角色权限处理
     *
     * @param $subject
     * @param $admin
     */
    private function sdjRole($subject, $admin)
    {
        //分配问答管理员角色
        $role = Role::where("slug", "sdj_manager")
            ->where("subject_id", $subject->id)
            ->first();

        if (!$role) {
            $role = Role::create([
                "name"       => "晒党建管理员",
                "slug"       => "sdj_manager",
                "subject_id" => $subject->id,
            ]);

            $permission = Permission::where("slug", "sdj")->first();

            $role->permissions()->save($permission);
        }

        $tempRole = $admin->roles()->where("slug", $role->slug)->first();
        if (!$tempRole) {
            $admin->roles()->save($role);
        }
    }

}
