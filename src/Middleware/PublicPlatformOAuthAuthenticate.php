<?php

namespace Overtrue\LaravelWeChat\Middleware;


use Closure;
use Illuminate\Support\Facades\Event;
use Mallto\Admin\SubjectUtils;
use Overtrue\LaravelWeChat\Events\WeChatUserAuthorized;
use Overtrue\LaravelWeChat\WechatUtils;

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
        //从这个config中获取公众号对应授权模式:静默授权还是请求用户信息
        $config = config(\sprintf('wechat.official_account.%s', $account), []);
        $openPlatform = \EasyWeChat::openPlatform(); // 开放平台
        list($appId, $refreshToken) = $this->wechatUtils->createAuthorizerApplicationParams($request);
        $officialAccount = $openPlatform->officialAccount($appId, $refreshToken);

        $requestScopes = $request->scopes;
        if ($requestScopes && in_array($requestScopes, ['snsapi_base', 'snsapi_userinfo'])) {
            $scopes = $requestScopes ?: ($scopes ?: array_get($config, 'oauth.scopes', ['snsapi_base']));
        } else {
            $scopes = $scopes ?: array_get($config, 'oauth.scopes', ['snsapi_base']);
        }


        if (is_string($scopes)) {
            $scopes = array_map('trim', explode(',', $scopes));
        }

        $session = session($sessionKey, []);


        if (!$session || $this->needReauth($scopes, $sessionKey)) {
            if ($request->has('code')) {
                //code可能被用过,用户在微信中进行页面回退操作的时候
                $user = $officialAccount->oauth->user();

                if (!$user || is_null($user->id)) {
                    //这里最常出现的错误就是access_token不可用了
                    //目前的处理是让用户重新请求微信授权,过程中就重新获取新的token了
                    \Log::warning("微信授权失败,没有正确获取用户信息");
                    \Log::warning(json_decode(json_encode($user), true));
                    \Log::warning($request->fullUrl());
                    
                    session()->forget($sessionKey);

                    return $officialAccount->oauth->scopes($scopes)->redirect($request->fullUrl());
                }

                session([$sessionKey => $user ?? []]);
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
    protected function needReauth($scopes, $sessionKey)
    {
        return 'snsapi_base' == session($sessionKey.'.original.scope') && in_array('snsapi_userinfo', $scopes);
    }
}
