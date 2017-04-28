<?php
namespace Overtrue\LaravelWechat\Model;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 07/01/2017
 * Time: 3:11 PM
 */
class WechatUserInfoRepository
{

    /**
     * 创建或者更新一个微信用户
     *
     * @param $wechatUser
     * @param $app_id
     */
    public function createOrUpdate($wechatUser, $app_id)
    {
        $wechatUser=$wechatUser["original"];
        if (isset($wechatUser['openid'])) {

            $user = WechatUserInfo::where('openid', $wechatUser['openid'])->first();
            if ($user) {
                $this->updateUser($user, $wechatUser, $app_id);
            } else {
                $this->createUser($wechatUser, $app_id);
            }
        }
    }

    /**
     * 更新user
     *
     * @param $user
     * @param $wechatInfoArr
     * @param $app_id
     * @return mixed
     * @internal param array $weChatUser
     */
    private function updateUser($user, $wechatInfoArr, $app_id)
    {
        return $user->update($this->makeUserArr($wechatInfoArr, $app_id));
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param $wechatInfoArr
     * @param $app_id
     * @return static
     * @internal param $weChatUser
     */
    private function createUser($wechatInfoArr, $app_id)
    {
        return WechatUserInfo::create($this->makeUserArr($wechatInfoArr, $app_id));
    }


    private function makeUserArr($wechatInfoArr, $app_id)
    {
        $data = [
            'openid'   => $wechatInfoArr['openid'],
            'nickname' => $wechatInfoArr['nickname'],
            'avatar'   => $wechatInfoArr['headimgurl'],
            'sex'      => $wechatInfoArr['sex'],
            'language' => $wechatInfoArr['language'],
            'city'     => $wechatInfoArr['city'],
            'province' => $wechatInfoArr['province'],
            'country'  => $wechatInfoArr['country'],
            'app_id'   => $app_id,
        ];
        return $data;
    }


}
