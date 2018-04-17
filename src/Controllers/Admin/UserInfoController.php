<?php
namespace Overtrue\LaravelWechat\Controllers\Admin;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Mallto\Admin\Controllers\Base\AdminCommonController;
use Overtrue\LaravelWechat\Model\WechatAuthInfo;
use Overtrue\LaravelWechat\Model\WechatUserInfo;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 25/04/2017
 * Time: 4:32 PM
 */
class UserInfoController extends AdminCommonController
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
        $grid->openid();
        $grid->nickname();
        $grid->sex();
        $grid->language();
        $grid->city();
        $grid->province();
        $grid->country();
        $grid->app_id();
        $grid->wechat_name("公众号")->display(function(){
            $appId=$this->app_id;
            $info=WechatAuthInfo::where("authorizer_appid",$appId)->first();
            return $info->nick_name;
        });

        $grid->filter(function($filter){
            $filter->like("app_id","app_id");
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
