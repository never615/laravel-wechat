<?php
namespace Overtrue\LaravelWechat;

use App\Exceptions\InvalidParamException;
use App\Exceptions\PermissionDeniedException;
use App\Exceptions\ResourceException;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Overtrue\LaravelWechat\Model\WechatAuthInfo;

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 18/04/2017
 * Time: 2:55 PM
 */
class WechatUtils
{
    /**
     * get appid from url.  [appid].xxx.com
     *
     * @param $request
     * @return mixed
     */
    public static function getAppid($request)
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


    public static function getUUID($request)
    {

        $uuid = Request::header("UUID");
        if ($uuid) {
            return $uuid;
        }

        $uuid = $request->uuid;
        if ($uuid) {
            return $uuid;
        }

        return null;

    }


    public static function getRefreshToken($appId)
    {
        $wechatAuthInfo = WechatAuthInfo::where("authorizer_appid", $appId)->first();
        if (!$wechatAuthInfo) {
            throw new PermissionDeniedException("公众号主体未授权");
        }

        return $wechatAuthInfo->authorizer_refresh_token;
    }


    /**
     * 获取createAuthorizerApplication的参数
     *
     * @param $reuqest
     * @return array
     */
    public static function createAuthorizerApplicationParams($reuqest)
    {
        $appId = self::getAppid($reuqest);
        if ($appId) {
            return [
                $appId,
                self::getRefreshToken($appId),
            ];
        }

        $UUID = self::getUUID($reuqest);
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

    public static function jsConfig($appId)
    {
        $wechat = app("wechat");
        $refreshToken = self::getRefreshToken($appId);
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
