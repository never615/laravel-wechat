<?php
namespace Overtrue\LaravelWechat\Controllers\Admin;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Overtrue\LaravelWechat\Model\WechatAuthInfo;
use Overtrue\LaravelWechat\Model\WechatCorpAuth;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 25/04/2017
 * Time: 4:32 PM
 */
class CorpAuthInfoController extends \Encore\Admin\Controllers\Base\AdminCommonController
{

    /**
     * 获取这个模块的标题
     *
     * @return mixed
     */
    protected function getHeaderTitle()
    {
        return "企业授权管理";
    }

    /**
     * 获取这个模块的Model
     *
     * @return mixed
     */
    protected function getModel()
    {
        return WechatCorpAuth::class;
    }

    protected function gridOption(Grid $grid)
    {
        $grid->corp_name("企业名");
        $grid->corp_id("企业id");
        $grid->permanent_code("授权码");
        $grid->uuid();
    }



    protected function formOption(Form $form)
    {
        $form->display("corp_name", "企业名");
        $form->display("corp_id", "企业id");
        $form->display("permanent_code", "授权码");
        $form->display("uuid");
        $form->display("auth_info")->with(function($value){
            return \GuzzleHttp\json_encode($value);
        });

    }
}
