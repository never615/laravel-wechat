<?php

namespace Overtrue\LaravelWechat\Middleware;


use Cache;
use Closure;
use EasyWeChat\Foundation\Application;
use Event;
use Illuminate\Support\Facades\Request;
use Log;
use Overtrue\LaravelWechat\Events\WeChatUserAuthorized;
use Overtrue\LaravelWechat\Model\WechatCorpAuthRepository;
use Overtrue\LaravelWechat\Model\WechatCorpUserInfoRepository;
use Overtrue\LaravelWechat\WechatUtils;

/**
 * 微信企业号套件
 * 获取用户授权信息
 * 的中间件
 *
 * Created by PhpStorm.
 * User: never615
 * Date: 15/12/2016
 * Time: 4:57 PM
 */
class CorpServerOAuthAuthenticate
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
     * @var WechatCorpAuthRepository
     */
    private $wechatCorpAuthRepository;


    /**
     * Inject the wechat service.
     *
     * @param Application                  $wechat
     * @param WechatCorpUserInfoRepository $userInfoRepository
     * @param WechatUtils                  $wechatUtils
     * @param WechatCorpAuthRepository     $wechatCorpAuthRepository
     */
    public function __construct(
        Application $wechat,
        WechatCorpUserInfoRepository $userInfoRepository,
        WechatUtils $wechatUtils,
        WechatCorpAuthRepository $wechatCorpAuthRepository
    ) {
        $this->wechat = $wechat;
        $this->userInfoRepository = $userInfoRepository;
        $this->wechatUtils = $wechatUtils;
        $this->wechatCorpAuthRepository = $wechatCorpAuthRepository;
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
        $uuid = $this->wechatUtils->getUUID($request);

//        session()->forget('wechat.oauth_user'.$uuid);


        list($corpId, $permanentCode) = $this->wechatUtils->createAuthorizerApplicationParamsByCorp($request);
        $corp_server_qa = $this->wechat->corp_server_qa;
        $app = $corp_server_qa->createAuthorizerApplication($corpId, $permanentCode);

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
            $fullUrl = $request->fullUrl();
            if ($request->has('code')) {

                if (Cache::has("wechat.oauth_code".$request->code)) {
                    //code已经被用过了
                    Cache::forget("wechat.oauth_code".$request->code);

                    session()->forget('wechat.oauth_user'.$uuid);
                    $agentId = $this->wechatCorpAuthRepository->getAgentId($corpId, 1);

                    return $app->oauth->agent($agentId)->scopes($scopes)->redirect($fullUrl);
                } else {
                    Cache::put("wechat.oauth_code".$request->code, $request->code, 5);
                }

                $user = $app->oauth->user();

                session(['wechat.oauth_user'.$uuid => $user]);

                $isNewSession = true;
                $this->userInfoRepository->createOrUpdate($user, $corpId);
                Event::fire(new WeChatUserAuthorized($user, $isNewSession));


                return redirect()->to($this->getTargetUrl($request));
            }

            session()->forget('wechat.oauth_user'.$uuid);
            $agentId = $this->wechatCorpAuthRepository->getAgentId($corpId, 1);

            return $app->oauth->agent($agentId)->scopes($scopes)->redirect($fullUrl);
        }

        $this->userInfoRepository->createOrUpdate(session('wechat.oauth_user'.$uuid), $corpId);
        Event::fire(new WeChatUserAuthorized(session('wechat.oauth_user'.$uuid), $isNewSession));

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

        $url = $request->url().(empty($queries) ? '' : '?'.http_build_query($queries));

        return $url;
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
