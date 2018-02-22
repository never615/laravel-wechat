<?php

namespace Overtrue\LaravelWechat\Middleware;


use Closure;
use EasyWeChat\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mallto\Tool\Exception\ResourceException;
use Mallto\Tool\Utils\SubjectUtils;
use Overtrue\LaravelWechat\Events\WeChatUserAuthorized;
use Overtrue\LaravelWechat\Model\WechatUserInfoRepository;
use Overtrue\LaravelWechat\WechatUtils;
use Overtrue\Socialite\AuthorizeFailedException;

/**
 * 微信开放平台
 * 代公众号获取用户授权信息
 * 中间件
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
     * @var WechatUtils
     */
    private $wechatUtils;


    /**
     * Inject the wechat service.
     *
     * @param Application              $wechat
     * @param WechatUserInfoRepository $userInfoRepository
     * @param WechatUtils              $wechatUtils
     */
    public function __construct(
        Application $wechat,
        WechatUserInfoRepository $userInfoRepository,
        WechatUtils $wechatUtils
    ) {
        $this->wechat = $wechat;
        $this->userInfoRepository = $userInfoRepository;
        $this->wechatUtils = $wechatUtils;
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
        $uuid = SubjectUtils::getUUID($request);
        list($appId, $refreshToken) = $this->wechatUtils->createAuthorizerApplicationParams($request);
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

        if (!session('wechat.oauth_user'.$uuid) || $this->needReauth($scopes)) {
            if ($request->has('code')) {
//                if (Cache::has("wechat.oauth_code".$request->code)) {
//                    //code已经被用过了
//                    Cache::forget("wechat.oauth_code".$request->code);
//
//                    session()->forget('wechat.oauth_user'.$uuid);
//
////                    $request->fullUrl()
//                    \Log::error("code被使用");
//                    \Log::warning($request->fullUrl());
//
//                    return $app->oauth->scopes($scopes)->redirect($request->fullUrl());
//                } else {
//                    Cache::put("wechat.oauth_code".$request->code, $request->code, 5);
//                }

//                $user = $app->oauth->user();


                try {
                    $user = $app->oauth->user();
                } catch (AuthorizeFailedException $e) {
                    \Log::error('authorizeFailedExcetion');
                    \Log::error($e->getMessage());
                    \Log::warning($e->getTraceAsString());
                    \Log::warning($request->fullUrl());

                    Cache::forget("wechat.oauth_code".$request->code);

                    session()->forget('wechat.oauth_user'.$uuid);

                    return $app->oauth->scopes($scopes)->redirect($request->fullUrl());
                }


                session(['wechat.oauth_user'.$uuid => $user]);
                $isNewSession = true;
                $this->userInfoRepository->createOrUpdate($user, $appId);
                Event::fire(new WeChatUserAuthorized(session('wechat.oauth_user'.$uuid), $isNewSession));

                return redirect()->to($this->getTargetUrl($request));
            }

            session()->forget('wechat.oauth_user'.$uuid);

            return $app->oauth->scopes($scopes)->redirect($request->fullUrl());
        }

        $this->userInfoRepository->createOrUpdate(session('wechat.oauth_user'.$uuid), $appId);
        Event::fire(new WeChatUserAuthorized(session('wechat.oauth_user'.$uuid), $isNewSession));

        $tempUser = session('wechat.oauth_user'.$uuid);
        if ($tempUser && is_null($tempUser->id)) {
            session()->forget('wechat.oauth_user'.$uuid);
            throw new ResourceException("微信授权失败");
        }

        return $next($request);
    }

    /**
     * Build the target business url.
     *
     * @param $request
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
