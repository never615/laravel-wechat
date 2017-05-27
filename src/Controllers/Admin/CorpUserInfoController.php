<?php
namespace Overtrue\LaravelWechat\Controllers\Admin;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Overtrue\LaravelWechat\Model\WechatAuthInfo;
use Overtrue\LaravelWechat\Model\WechatCorpAuth;
use Overtrue\LaravelWechat\Model\WechatCorpUserInfo;
use Overtrue\LaravelWechat\Model\WechatUserInfo;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 25/04/2017
 * Time: 4:32 PM
 */
class CorpUserInfoController extends \Encore\Admin\Controllers\Base\AdminCommonController
{

    /**
     * 获取这个模块的标题
     *
     * @return mixed
     */
    protected function getHeaderTitle()
    {
        return "企业用户管理";
    }

    /**
     * 获取这个模块的Model
     *
     * @return mixed
     */
    protected function getModel()
    {
        return WechatCorpUserInfo::class;
    }

    protected function gridOption(Grid $grid)
    {
        $grid->user_id();
        $grid->name();
        $grid->gender();
//        $grid->department()->display(function($value){
//            return \GuzzleHttp\json_encode($value);
//        });
//        $grid->position();
        $grid->mobile();
        $grid->email();
        $grid->corp_id();
        $grid->corp_name("企业号")->display(function(){
            $corpId=$this->corp_id;
            $info=WechatCorpAuth::where("corp_id",$corpId)->first();
            return $info->corp_name;
        });

        $grid->filter(function($filter){
            $filter->like("app_id","app_id");
        });

    }

    protected function formOption(Form $form)
    {
        $form->display("user_id");
        $form->display("name");
        $form->display("gender");
        $form->display("department")->with(function($value){
            return \GuzzleHttp\json_encode($value);
        });

        $form->display("position");
        $form->display("mobile");
        $form->display("email");
        $form->display("corp_id");
        $form->display("avatar")->with(function ($value) {
            return "<img src='$value' style='height: 80px'/>";
        });
    }
}
