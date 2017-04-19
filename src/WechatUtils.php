<?php
namespace Overtrue\LaravelWechat;

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

        $url = $request->url();
        //获取第一段域名
        $urlArr = explode(".", explode("//", $url)[1]);

        return $urlArr[0];
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

        return [
            $appId,
            self::getRefreshToken($appId),
        ];
    }

}
