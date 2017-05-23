<?php
namespace Overtrue\LaravelWechat\Model;

use App\Exceptions\PermissionDeniedException;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 07/01/2017
 * Time: 3:11 PM
 */
class WechatCorpAuthRepository
{

    /**
     * 获取永久授权码
     *
     * @param $corpId
     * @return mixed
     */
    public function getPermanentCode($corpId)
    {
        $corpAuth = WechatCorpAuth::where("corp_id", $corpId)->first();
        if (!$corpAuth) {
            throw new PermissionDeniedException("企业号主体未授权");
        }

        return $corpAuth->permanent_code;
    }


}
