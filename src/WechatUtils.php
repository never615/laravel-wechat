<?php
namespace Overtrue\LaravelWechat;

use App\Exceptions\InvalidParamException;
use App\Exceptions\ResourceException;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Overtrue\LaravelWechat\Model\WechatAuthInfo;
use Overtrue\LaravelWechat\Model\WechatAuthInfoRepository;
use Overtrue\LaravelWechat\Model\WechatCorpAuth;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 18/04/2017
 * Time: 2:55 PM
 */
class WechatUtils
{
    /**
     * @var WechatAuthInfoRepository
     */
    private $authInfoRepository;


    /**
     * WechatUtils constructor.
     *
     * @param WechatAuthInfoRepository $authInfoRepository
     */
    public function __construct(WechatAuthInfoRepository $authInfoRepository)
    {
        $this->authInfoRepository = $authInfoRepository;
    }


    /**
     * get appid from url.  [appid].xxx.com
     *
     * @param $request
     * @return mixed
     */
    public function getAppid($request)
    {

        $appId = Request::header("APP_ID");
        if ($appId) {
            return $appId;
        }

        $appId = $request->app_id;
        if ($appId) {
            return $appId;
        }


        return null;


        //下面的方式暂时不用,应为一个域名要使用一个https证书,太麻烦.
//        $url = $request->url();
//        //获取第一段域名
//        $urlArr = explode(".", explode("//", $url)[1]);
//
//        return $urlArr[0];
    }


    public function getUUID($request)
    {

        $uuid = Request::header("UUID");
        if ($uuid) {
            return $uuid;
        }

        $uuid = $request->uuid;
        if ($uuid) {
            return $uuid;
        }

        throw new InvalidParamException("无效的参数,无法得知微信主体");
    }


    /**
     * 获取createAuthorizerApplication的参数
     *
     * @param $request
     * @return array
     */
    public function createAuthorizerApplicationParams($request)
    {
        $appId = $this->getAppid($request);
        if ($appId) {
            return [
                $appId,
                $this->authInfoRepository->getRefreshToken($appId),
            ];
        }

        $UUID = $this->getUUID($request);
        if ($UUID) {
            //根据subjectId查询appId
            $wechatAuthInfo = WechatAuthInfo::where("uuid", $UUID)->first();
            if ($wechatAuthInfo) {
                $appId = $wechatAuthInfo->authorizer_appid;

                return [
                    $appId,
                    $wechatAuthInfo->authorizer_refresh_token,
                ];
            }
        }

        throw new InvalidParamException("无效的参数,无法得知微信主体");
    }


    /**
     * 获取createAuthorizerApplication的参数 企业号使用
     *
     * @param $request
     * @return array
     */
    public function createAuthorizerApplicationParamsByCorp($request)
    {
        $UUID = $this->getUUID($request);
        if ($UUID) {
            //根据subjectId查询appId
            $wechatAuthInfo = WechatCorpAuth::where("corp_id", $UUID)->first();
            if ($wechatAuthInfo) {
                $corpId = $wechatAuthInfo->corp_id;

                return [
                    $corpId,
                    $wechatAuthInfo->permanent_code,
                ];
            }
        }

        throw new InvalidParamException("无效的参数,无法得知微信主体");
    }


    public function jsConfig($appId)
    {
        $wechat = app("wechat");
        $refreshToken = $this->authInfoRepository->getRefreshToken($appId);
        // 传递 AuthorizerAppId 和 AuthorizerRefreshToken（注意不是 AuthorizerAccessToken）即可。
        $app = $wechat->open_platform->createAuthorizerApplication($appId, $refreshToken);
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

        return $result;
    }

}
