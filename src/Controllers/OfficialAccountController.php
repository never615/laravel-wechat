<?php

namespace Overtrue\LaravelWeChat\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\Tool\Exception\ResourceException;
use Mallto\Tool\Utils\ResponseUtils;
use Mallto\Tool\Utils\UrlUtils;
use Overtrue\LaravelWeChat\WechatUtils;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class OfficialAccountController extends \Illuminate\Routing\Controller
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
        $redirectUrl = $request->redirect_url;

        //检查回调域名
        $callbackDomain = config("app.oauth_callback_domain");
        $domains = explode(",", $callbackDomain);
        $requestDomain = UrlUtils::getDomain($redirectUrl);
        $isAuth = false;

        //先检查有没有*号开头的域名
        foreach ($domains as $domain) {
            if (starts_with($domain, "*.")) {
                $domain = str_replace("*.", "", $domain);
                if (ends_with($requestDomain, $domain)) {
                    $isAuth = true;
                    break;
                }
            }
        }

        if ($isAuth & in_array($requestDomain, $domains)) {
//            throw new PermissionDeniedException("回调域名不可信:".$requestDomain);
            $isAuth = true;
        }

        if (!$isAuth) {
            throw new PermissionDeniedException("回调域名不可信:".$requestDomain);
        }
        $account = 'default';
        $sessionKey = \sprintf('wechat.oauth_user.%s', $account);

        $wechatUser = session($sessionKey);

        $cryptOpenId = encrypt($wechatUser->id);

        return ResponseUtils::responseBasicByRedirect($redirectUrl, ["openid" => $cryptOpenId]);
    }

    /**
     * 测试
     *
     * @param Request $request
     */
    public function userTest(Request $request)
    {
        $sessionKey = \sprintf('wechat.oauth_user.%s', 'default');

        $user = session($sessionKey); // 拿到授权用户资料
        echo $cryptOpenId = encrypt($user->id);
        dd($user);
    }

    /**
     * 公众号获取jssdk配置
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function jsConfig()
    {
        $officialAccount = \EasyWeChat::officialAccount(); // 公众号

        $js = $officialAccount->jssdk;
        $url = Input::get("url");
        if (is_null($url)) {
            throw new ResourceException("url is null");
        }
        $js->setUrl($url);
        $result = $js->buildConfig([
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
