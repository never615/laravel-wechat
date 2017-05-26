<?php
namespace Overtrue\LaravelWechat\Controllers\CorpServer;


use App\Exceptions\ResourceException;
use EasyWeChat\Foundation\Application;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Input;
use Overtrue\LaravelWechat\Model\WechatCorpAuth;
use Overtrue\LaravelWechat\Model\WechatCorpAuthRepository;
use Overtrue\LaravelWechat\WechatUtils;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 11/04/2017
 * Time: 7:15 PM
 */
class CorpController extends Controller
{

    private $wechat;
    private $corp_server_qa;
    /**
     * @var WechatUtils
     */
    private $wechatUtils;


    /**
     * WechatOpenPlatformController constructor.
     *
     * @param Application $wechat
     * @param WechatUtils $wechatUtils
     */
    public function __construct(
        Application $wechat,
        WechatUtils $wechatUtils
    ) {
        $this->wechat = $wechat;
        $this->corp_server_qa = $wechat->corp_server_qa;
        $this->wechatUtils = $wechatUtils;
    }

    /**
     * js签名
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return
     * @throws ResourceException
     */
    public function jsConfig(\Symfony\Component\HttpFoundation\Request $request)
    {

        list($corpId, $permanentCode) = $this->wechatUtils->createAuthorizerApplicationParamsByCorp($request);
        $corp_server_qa = $this->wechat->corp_server_qa;
        $app = $corp_server_qa->createAuthorizerApplication($corpId, $permanentCode);
        // 调用方式与普通调用一致。
        $js = $app->js;
        $url = Input::get("url");
        if (is_null($url)) {
            throw new ResourceException("url is null");
        }
        $js->setUrl($url);
        $result = $js->config([
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
        ], $debug = false, $beta = false, $json = true);

        return response($result);
    }
}
