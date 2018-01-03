<?php

namespace Overtrue\LaravelWechat\Controllers;

use EasyWeChat\Foundation\Application;
use Illuminate\Support\Facades\Input;
use Mallto\Tool\Exception\ResourceException;
use Overtrue\LaravelWechat\WechatUtils;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class JsconfigController extends \Illuminate\Routing\Controller
{
    /**
     * @var WechatUtils
     */
    private $wechatUtils;

    /**
     * WechatOAuthController constructor.
     *
     * @param WechatUtils $wechatUtils
     */
    public function __construct(WechatUtils $wechatUtils)
    {
        $this->wechatUtils = $wechatUtils;
    }


    /**
     * 公众号获取jssdk配置
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function jsConfig()
    {
        $app = new Application(config('wechat'));

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
            'openLocation',
        ], $debug = false, $beta = false, $json = true);

        return response($result);
    }

}
