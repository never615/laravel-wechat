<?php
namespace Overtrue\LaravelWechat\Controllers;

use App\Lib\ResponseUtils;
use Illuminate\Support\Facades\Log;
use Overtrue\LaravelWechat\WechatUtils;
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
        $uuid = $this->wechatUtils->getUUID($request);
        $redirectUrl = $request->redirect_url;
        $wechatUser = session('wechat.oauth_user'.$uuid);

        $cryptOpenId = encrypt($wechatUser->id);

        return ResponseUtils::responseBasicByRedirect($redirectUrl, ["openid" => $cryptOpenId]);
//            ->cookie('openid', $wechatUser->id, 1000, null, null, false, false);
        
//        return redirect($redirectUrl)
//            ->cookie('openid', $wechatUser->id, 1000, null, null, false, false);
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
        $uuid = $this->wechatUtils->getUUID($request);
        $user = session('wechat.oauth_user'.$uuid); // 拿到授权用户资料
        Log::info($uuid);
        echo $uuid;
        dd($user);
    }

}
