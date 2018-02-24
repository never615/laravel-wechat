<?php

namespace Overtrue\LaravelWeChat\Middleware;


use Closure;
use Illuminate\Support\Facades\Event;
use Mallto\Tool\Utils\SubjectUtils;
use Overtrue\LaravelWechat\Events\WeChatUserAuthorized;
use Overtrue\LaravelWechat\WechatUtils;

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
     * @var WechatUtils
     */
    private $wechatUtils;


    /**
     * Inject the wechat service.
     *
     * @param WechatUtils $wechatUtils
     */
    public function __construct(
        WechatUtils $wechatUtils
    ) {
        $this->wechatUtils = $wechatUtils;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string                   $account
     * @param string|null              $scopes
     * @return mixed
     */
    public function handle($request, Closure $next, $account = 'default', $scopes = null)
    {

        // $account 与 $scopes 写反的情况
        if (is_array($scopes) || (\is_string($account) && str_is('snsapi_*', $account))) {
            list($account, $scopes) = [$scopes, $account];
            $account || $account = 'default';
        }
        $uuid = SubjectUtils::getUUID($request);

        $isNewSession = false;
        $sessionKey = \sprintf('wechat.oauth_user.%s.%s', $account, $uuid);
        $config = config(\sprintf('wechat.official_account.%s', $account), []);
        $openPlatform = \EasyWeChat::openPlatform(); // 开放平台
        list($appId, $refreshToken) = $this->wechatUtils->createAuthorizerApplicationParams($request);
        $officialAccount = $openPlatform->officialAccount($appId, $refreshToken);
        $scopes = $scopes ?: array_get($config, 'oauth.scopes', ['snsapi_base']);
        if (is_string($scopes)) {
            $scopes = array_map('trim', explode(',', $scopes));
        }


        $session = session($sessionKey, []);


        if (!$session || $this->needReauth($scopes,$sessionKey)) {
            if ($request->has('code')) {
                session([$sessionKey => $officialAccount->oauth->user() ?? []]);
                $isNewSession = true;
                Event::fire(new WeChatUserAuthorized(session($sessionKey), $isNewSession, $account, $appId));

                return redirect()->to($this->getTargetUrl($request));
            }
            session()->forget($sessionKey);

            return $officialAccount->oauth->scopes($scopes)->redirect($request->fullUrl());
        }
        Event::fire(new WeChatUserAuthorized(session($sessionKey), $isNewSession, $account, $appId));

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
     * @param array $scopes
     *
     * @param       $sessionKey
     * @return bool
     */
    protected function needReauth($scopes,$sessionKey)
    {
        return 'snsapi_base' == session($sessionKey.'.original.scope') && in_array('snsapi_userinfo', $scopes);
    }
}
