<?php
namespace Overtrue\LaravelWechat;

use App\Exceptions\InvalidParamException;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Support\Facades\Request;

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
        $url = $request->url();
        //获取第一段域名
        $urlArr = explode(".", explode("//", $url)[1]);

        return $urlArr[0];
    }


    public static function getSubjectId($request)
    {

        $subjectId = Request::header("Subject_Id");
        if ($subjectId) {
            return $subjectId;
        }

        $subjectId = $request->app_id;
        if ($subjectId) {
            return $subjectId;
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

        $subjectId = self::getSubjectId($reuqest);
        if ($subjectId) {
            //根据subjectId查询appId
            $wechatAuthInfo = WechatAuthInfo::where("subject_id", $subjectId)->first();
            if ($wechatAuthInfo) {
                $appId = $wechatAuthInfo->authorizer_appid;

                return [
                    $appId,
                    self::getRefreshToken($appId),
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
