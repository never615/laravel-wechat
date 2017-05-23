<?php
namespace Overtrue\LaravelWechat\Model;

use App\Exceptions\PermissionDeniedException;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 07/01/2017
 * Time: 3:11 PM
 */
class WechatAuthInfoRepository
{

    /**
     * 获取refreshToken
     *
     * @param $appId
     * @return mixed
     */
    public function getRefreshToken($appId)
    {
        $wechatAuthInfo = WechatAuthInfo::where("authorizer_appid", $appId)->first();
        if (!$wechatAuthInfo) {
            throw new PermissionDeniedException("公众号主体未授权");
        }

        return $wechatAuthInfo->authorizer_refresh_token;
    }

    
    
    
}
