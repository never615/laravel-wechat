<?php
namespace Overtrue\LaravelWechat\Controllers;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
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
     */
    public function oauth(Request $request)
    {

        $all=Input::all();
        Log::info($all);

        $redirectUrl = $request->redirect_url;
        $wechatUser = session('wechat.oauth_user');
        Log::info("oauth");
//        Log::info(\GuzzleHttp\json_encode($wechatUser));

        return redirect($redirectUrl)
            ->cookie('openid',$wechatUser->id, 1000, null, null, false, false);
//        ->cookie('openid', encrypt($wechatUser->id), 1000, null, null, false, false);

//        return redirect($redirectUrl, 302,
//            ["wechat_user" => \GuzzleHttp\json_encode($wechatUser)]);
    }

}
