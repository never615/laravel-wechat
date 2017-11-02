<?php

namespace Overtrue\LaravelWechat;

use Encore\Admin\AppUtils;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Mallto\Tool\Exception\InvalidParamException;
use Mallto\Tool\Exception\ResourceException;
use Mallto\Tool\Utils\SubjectUtils;
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
    }


    /**
     * 从开放平台创建代公众号实现业务的app
     *
     * @param $openPlatform
     * @return array
     */
    public function createAppFromOpenPlatform($openPlatform)
    {
        $uuid = SubjectUtils::getUUID();
        if ($uuid) {
            $wechatAuthInfo = WechatAuthInfo::where("uuid", $uuid)->first();
            if ($wechatAuthInfo) {
                $appId = $wechatAuthInfo->authorizer_appid;

                return $openPlatform->createAuthorizerApplication($appId, $wechatAuthInfo->authorizer_refresh_token);
            } else {
                return false;
            }

        } else {
            throw new InvalidParamException("无效的uuid,无法得知微信主体");
        }
    }

    /**
     * 获取createAuthorizerApplication的参数
     *
     * @deprecated
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
        $UUID = SubjectUtils::getUUID();

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
        $UUID = SubjectUtils::getUUID();
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
