<?php

namespace Overtrue\LaravelWechat\Middleware;


use Cache;
use Closure;
use EasyWeChat\Foundation\Application;
use Event;
use Log;
use Illuminate\Support\Facades\Request;
use Overtrue\LaravelWechat\Events\WeChatUserAuthorized;
use Overtrue\LaravelWechat\WechatUserInfo;
use Overtrue\LaravelWechat\WechatUserInfoRepository;
use Overtrue\LaravelWechat\WechatUtils;

/**
 * 微信开放平台替公众号获取用户授权中间件
 *
 * Created by PhpStorm.
 * User: never615
 * Date: 15/12/2016
 * Time: 4:57 PM
 */
class PublicPlatformOAuthAuthenticate
{

    /**
     * Use Service Container would be much artisan.
     */
    private $wechat;

    private $userInfoRepository;


    /**
     * Inject the wechat service.
     *
     * @param Application              $wechat
     * @param WechatUserInfoRepository $userInfoRepository
     */
    public function __construct(Application $wechat, WechatUserInfoRepository $userInfoRepository)
    {
        $this->wechat = $wechat;
        $this->userInfoRepository = $userInfoRepository;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string|null              $scopes
     * @return mixed
     *
     */
    public function handle($request, Closure $next, $scopes = null)
    {
        list($appId, $refreshToken) = WechatUtils::createAuthorizerApplicationParams($request);
        $openPlatform = $this->wechat->open_platform;
        $app = $openPlatform->createAuthorizerApplication($appId, $refreshToken);

        $isNewSession = false;
        $onlyRedirectInWeChatBrowser = config('wechat.oauth.only_wechat_browser', false);

        if ($onlyRedirectInWeChatBrowser && !$this->isWeChatBrowser($request)) {
            if (config('debug')) {
                Log::debug('[not wechat browser] skip wechat oauth redirect.');
            }

            return $next($request);
        }


        $scopes = $scopes ?: config('wechat.oauth.scopes', ['snsapi_base']);

        if (is_string($scopes)) {
            $scopes = array_map('trim', explode(',', $scopes));
        }

        if (!session('wechat.oauth_user') || $this->needReauth($scopes)) {
            $this->userInfoRepository->createOrUpdate($app->oauth->user(), $appId);
            if ($request->has('code')) {
                session(['wechat.oauth_user' => $app->oauth->user()]);
                $isNewSession = true;

                Event::fire(new WeChatUserAuthorized(session('wechat.oauth_user'), $isNewSession));

                return redirect()->to($this->getTargetUrl($request));
            }

            session()->forget('wechat.oauth_user');

            return $app->oauth->scopes($scopes)->redirect($request->fullUrl());
        }

        Event::fire(new WeChatUserAuthorized(session('wechat.oauth_user'), $isNewSession));
        return $next($request);
    }

    /**
     * Build the target business url.
     *
     * @param Request $request
     *
     * @return string
     */
    public function getTargetUrl($request)
    {
        $queries = array_except($request->query(), ['code', 'state']);

        return $request->url().(empty($queries) ? '' : '?'.http_build_query($queries));
    }

    /**
     * Is different scopes.
     *
     * @param  array $scopes
     *
     * @return bool
     */
    protected function needReauth($scopes)
    {
        return session('wechat.oauth_user.original.scope') == 'snsapi_base' && in_array("snsapi_userinfo", $scopes);
    }

    /**
     * Detect current user agent type.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    protected function isWeChatBrowser($request)
    {
        return strpos($request->header('user_agent'), 'MicroMessenger') !== false;
    }


}
