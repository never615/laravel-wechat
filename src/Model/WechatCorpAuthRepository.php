<?php
namespace Overtrue\LaravelWeChat\Model;

use Mallto\Tool\Exception\PermissionDeniedException;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 07/01/2017
 * Time: 3:11 PM
 */
class WechatCorpAuthRepository
{
    protected $connection = 'wechat_public';

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


    /**
     * @param $corpId
     * @param $appId ,服务商套件应用的id
     */
    public function getAgentId($corpId, $appId)
    {
        $authInfo = WechatCorpAuth::where('corp_id', $corpId)->first();
        if (!$authInfo) {
            throw new PermissionDeniedException("企业号未授权");
        }

        $agents = $authInfo->auth_info["auth_info"]['agent'];
        foreach ($agents as $agent) {
            if ($agent["appid"] == $appId) {
                return $agent["agentid"];
            }
        }

        throw new PermissionDeniedException("应用未授权:".$appId);
    }


}
