<?php
namespace Overtrue\LaravelWechat\Controllers;

use App\Lib\ResponseUtils;
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
     * 获得用户微信的授权信息,授权中转站
     *
     * 返回openid
     */
    public function oauth(Request $request)
    {
//        $all=Input::all();
//        Log::info($all);
        $redirectUrl = $request->redirect_url;
        $wechatUser = session('wechat.oauth_user');

        return ResponseUtils::responseBasicByRedirect2($redirectUrl,["openid"=> $wechatUser->id])
            ->cookie('openid', $wechatUser->id, 1000, null, null, false, false);

//        return redirect($redirectUrl)
//            ->cookie('openid', $wechatUser->id, 1000, null, null, false, false);
    }

    /**
     * 重定向携带微信用户信息
     *
     * @param Request $request
     * @return \App\Lib\Redirect
     */
    public function oauthInfo(Request $request)
    {
        $redirectUrl = $request->redirect_url;
        $wechatUser = session('wechat.oauth_user');

        return ResponseUtils::responseBasicByRedirect2($redirectUrl, $wechatUser);
    }

}
