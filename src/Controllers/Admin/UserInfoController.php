<?php

namespace Overtrue\LaravelWeChat\Controllers\Admin;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Overtrue\LaravelWeChat\Model\WechatAuthInfo;
use Overtrue\LaravelWeChat\Model\WechatUserInfo;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 25/04/2017
 * Time: 4:32 PM
 */
class UserInfoController extends \Encore\Admin\Controllers\Base\AdminCommonController
{

    /**
     * 获取这个模块的标题
     *
     * @return mixed
     */
    protected function getHeaderTitle()
    {
        return "微信用户管理";
    }

    /**
     * 获取这个模块的Model
     *
     * @return mixed
     */
    protected function getModel()
    {
        return WechatUserInfo::class;
    }

    protected function gridOption(Grid $grid)
    {
        $grid->openid()->sortable();
        $grid->nickname()->sortable();
        $grid->sex()->sortable();
        $grid->language()->sortable();
        $grid->city()->sortable();
        $grid->province()->sortable();
        $grid->country()->sortable();
        $grid->app_id()->sortable();
        $grid->wechat_name("公众号")->display(function () {
            $appId = $this->app_id;
            $info = WechatAuthInfo::where("authorizer_appid", $appId)->first();

            return $info->nick_name;
        })->sortable();

        $grid->filter(function ($filter) {
            $filter->ilike("nickname");
            $filter->like("app_id", "app_id");
            $filter->like("openid", "openid");
        });

    }

    protected function formOption(Form $form)
    {
        $form->display("openid");
        $form->display("nickname");
        $form->display("sex");
        $form->display("language");
        $form->display("city");
        $form->display("province");
        $form->display("country");
        $form->display("avatar")->with(function ($value) {
            return "<img src='$value' style='height: 80px'/>";
        });
        $form->display("app_id");
    }
}
