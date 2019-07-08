<?php

namespace Overtrue\LaravelWeChat\Controllers;

use Illuminate\Support\Facades\Log;
use Mallto\Admin\SubjectUtils;
use Mallto\Tool\Utils\ResponseUtils;
use Mallto\Tool\Utils\UrlUtils;
use Overtrue\LaravelWeChat\WechatUtils;
use Symfony\Component\HttpFoundation\Request;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class WechatOAuthController extends \Illuminate\Routing\Controller
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
     * 获得用户微信的授权信息,授权中转站
     *
     * 返回openid
     */
    public function oauth(Request $request)
    {
        $uuid = SubjectUtils::getUUID($request);
        $redirectUrl = $request->redirect_url;

        UrlUtils::checkDomainOAuth($redirectUrl);

        $sessionKey = \sprintf('wechat.oauth_user.%s.%s', 'default', $uuid);
        $wechatUser = session($sessionKey);

        $openid = $wechatUser->id.'|||'.time();
        $cryptOpenId = encrypt($openid);


        return ResponseUtils::responseBasicByRedirect($redirectUrl, [
            "openid" => $cryptOpenId,
        ]);
    }



//    /**
//     * 重定向携带微信用户信息
//     *
//     * @param Request $request
//     * @return \App\Lib\Redirect
//     */
//    public function oauthInfo(Request $request)
//    {
//        $redirectUrl = $request->redirect_url;
//        $wechatUser = session('wechat.oauth_user');
//
//        return ResponseUtils::responseBasicByRedirect2($redirectUrl, $wechatUser);
//    }

    /**
     * 测试
     *
     * @param Request $request
     */
    public function userTest(Request $request)
    {
        \Log::info('usertest');
        $uuid = SubjectUtils::getUUID($request);
        $sessionKey = \sprintf('wechat.oauth_user.%s.%s', 'default', $uuid);

        $user = session($sessionKey); // 拿到授权用户资料
        Log::info($uuid);
        echo $uuid;
        \Log::warning(json_decode(json_encode($user), true));
        dd($user);
    }

}
