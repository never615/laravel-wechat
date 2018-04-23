<?php
/**
 * Created by PhpStorm.
 * User: never615 <never615.com>
 * Date: 02/11/2017
 * Time: 5:43 PM
 */

namespace Overtrue\LaravelWeChat\Domain;


use Exception;
use Mallto\Tool\Exception\PermissionDeniedException;
use Overtrue\LaravelWeChat\Model\WechatAuthInfo;
use Overtrue\LaravelWeChat\Model\WechatUserInfo;

class WechatUsecase
{
    /**
     * 获取微信用户的信息
     *
     * @deprecated 使用WechatUserInfoRepository中的 getWechatUserInfo
     *
     *
     * @param      $uuid
     * @param      $openid
     * @return WechatUserInfo
     */
    public function getWechatUserInfo($uuid, $openid)
    {
        $mode = config("wechat.mode");
        if ($mode == 'open_platform' || empty($mode)) {
            //查询微信用户信息
            $wechatAuthInfo = WechatAuthInfo::where("uuid", $uuid)->first();
            if (!$wechatAuthInfo) {
                throw new PermissionDeniedException("公众号未授权");
            }
            $wechatUserInfo = WechatUserInfo::where("openid", $openid)
                ->where("app_id", $wechatAuthInfo->authorizer_appid)
                ->first();
        } else {
            //todo 公众号模式下多公众号支持
            $wechatUserInfo = WechatUserInfo::where("openid", $openid)
                ->first();
        }


        if (!$wechatUserInfo) {
            \Log::error("无法获取微信信息:".$openid.",".$uuid);
            $exception=new Exception();
            \Log::warning($exception->getTraceAsString());
            throw new PermissionDeniedException("openid未找到,请在微信内打开");
        }

        return $wechatUserInfo;
    }
}
