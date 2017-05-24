<?php
namespace Overtrue\LaravelWechat\Model;

use App\Exceptions\PermissionDeniedException;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 07/01/2017
 * Time: 3:11 PM
 */
class WechatCorpUserInfoRepository
{

    /**
     * 创建或者更新一个微信用户
     *
     * @param $wechatUser
     * @param $app_id
     */
    public function createOrUpdate($wechatUser, $app_id)
    {
        $wechatAuthInfo = WechatCorpAuth::where("corp_id", $app_id)->first();
        if (!$wechatAuthInfo) {
            throw new PermissionDeniedException("企业号号未授权");
        }

        if (isset($wechatUser["original"]['userid'])) {

            $user = WechatCorpUserInfo::where('user_id', $wechatUser['userid'])->first();
            if ($user) {
                $this->updateUser($user, $wechatUser, $app_id, $wechatAuthInfo->id);
            } else {
                $this->createUser($wechatUser, $app_id, $wechatAuthInfo->id);
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
    private function updateUser($user, $wechatInfoArr, $app_id, $authId)
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
    private function createUser($wechatInfoArr, $app_id, $authId)
    {
        return WechatCorpUserInfo::create($this->makeUserArr($wechatInfoArr, $app_id, $authId));
    }


    private function makeUserArr($wechatInfoArr, $app_id, $authId)
    {

        $mobile = "";
        $email = "";
        if (isset($wechatInfoArr['original']['mobile'])) {
            $mobile = $wechatInfoArr['original']['mobile'];
            $email = $wechatInfoArr['original']['email'];
        }
        $position = "";
        if (isset($wechatInfoArr['original']['position'])) {
            $position = $wechatInfoArr['original']['position'];
        }

        $data = [
            'user_id'             => $wechatInfoArr['original']['userid'],
            'name'                => $wechatInfoArr['original']['name'],
            'gender'              => $wechatInfoArr['original']['gender'],
            'department'          => $wechatInfoArr['original']['department'],
            'position'            => $position,
            'avatar'              => $wechatInfoArr['original']['avatar'],
            'mobile'              => $mobile,
            'email'               => $email,
            'corp_id'             => $app_id,
            "wechat_corp_auth_id" => $authId,
        ];

        return $data;
    }


}
