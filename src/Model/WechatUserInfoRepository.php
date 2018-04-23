<?php

namespace Overtrue\LaravelWeChat\Model;

use Exception;
use Mallto\Tool\Exception\PermissionDeniedException;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 07/01/2017
 * Time: 3:11 PM
 */
class WechatUserInfoRepository
{
    protected $connection = 'wechat_public';

    /**
     * 创建或者更新一个微信用户
     *
     * @param $wechatUser
     * @param $app_id
     */
    public function createOrUpdate($wechatUser, $app_id = null)
    {
        $mode = config("wechat.mode");
        if ($mode == 'open_platform' || empty($mode)) {
            $wechatAuthInfo = WechatAuthInfo::where("authorizer_appid", $app_id)->first();
            if (!$wechatAuthInfo) {
                throw new PermissionDeniedException("公众号未授权");
            }
            $wechatUser = $wechatUser["original"];
            if (isset($wechatUser['openid'])) {

                $user = WechatUserInfo::where('openid', $wechatUser['openid'])
                    ->where("app_id", $app_id)
                    ->first();
                if ($user) {
                    $this->updateUser($user, $wechatUser, $app_id, $wechatAuthInfo->id);
                } else {
                    $this->createUser($wechatUser, $app_id, $wechatAuthInfo->id);
                }
            }
        } else {
            //todo 公众号模式下多公众号支持

            $wechatUser = $wechatUser["original"];
            if (isset($wechatUser['openid'])) {

                $user = WechatUserInfo::where('openid', $wechatUser['openid'])
                    ->first();
                if ($user) {
                    $this->updateUser($user, $wechatUser);
                } else {
                    $this->createUser($wechatUser);
                }
            }
        }


    }

    /**
     * 更新user
     *
     * @param $user
     * @param $wechatInfoArr
     * @param $app_id
     * @param $authId
     * @return mixed
     * @internal param array $weChatUser
     */
    private function updateUser($user, $wechatInfoArr, $app_id = null, $authId = null)
    {
        return $user->update($this->makeUserArr($wechatInfoArr, $app_id, $authId));
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param $wechatInfoArr
     * @param $app_id
     * @param $authId
     * @return static
     * @internal param $weChatUser
     */
    private function createUser($wechatInfoArr, $app_id = null, $authId = null)
    {
        return WechatUserInfo::create($this->makeUserArr($wechatInfoArr, $app_id, $authId));
    }


    private function makeUserArr($wechatInfoArr, $app_id, $authId)
    {
        $unionid = "";
        if (isset($wechatInfoArr["unionid"])) {
            $unionid = $wechatInfoArr["unionid"];
        }

        $data = [
            'openid'              => $wechatInfoArr['openid'],
            'nickname'            => $wechatInfoArr['nickname'],
            'avatar'              => $wechatInfoArr['headimgurl'],
            'sex'                 => $wechatInfoArr['sex'],
            'language'            => $wechatInfoArr['language'],
            'city'                => $wechatInfoArr['city'],
            'province'            => $wechatInfoArr['province'],
            'country'             => $wechatInfoArr['country'],
            'app_id'              => $app_id,
            "wechat_auth_info_id" => $authId,
//            "unionid"             => $unionid,
        ];

        return $data;
    }

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
