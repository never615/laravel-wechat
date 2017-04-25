<?php
namespace Overtrue\LaravelWechat\Controllers\Admin;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Overtrue\LaravelWechat\WechatAuthInfo;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 25/04/2017
 * Time: 4:32 PM
 */
class AuthInfoController extends \Encore\Admin\Controllers\Base\AdminCommonController
{

    /**
     * 获取这个模块的标题
     *
     * @return mixed
     */
    protected function getHeaderTitle()
    {
        return "微信授权管理";
    }

    /**
     * 获取这个模块的Model
     *
     * @return mixed
     */
    protected function getModel()
    {
        return WechatAuthInfo::class;
    }

    protected function gridOption(Grid $grid)
    {
        $grid->nick_name("公众号名");
        $grid->authorizer_appid("AppId");
        $grid->principal_name("公司");
        $grid->alias("公众号");
    }

    protected function defaultFormOption(Form $form)
    {
        $form->display('id', 'ID');
        $this->formOption($form);
//

        $form->display('created_at', trans('admin::lang.created_at'));
        $form->display('updated_at', trans('admin::lang.updated_at'));
    }

    protected function formOption(Form $form)
    {
        $form->display("nick_name", "公众号名");
        $form->display("authorizer_appid", "AppId");
        $form->display("principal_name", "公司");
        $form->display("alias", "公众号");
        $form->text("uuid");

    }
}
