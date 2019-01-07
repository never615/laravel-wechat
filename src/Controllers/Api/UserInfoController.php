<?php

namespace Overtrue\LaravelWeChat\Controllers\Api;


use Illuminate\Http\Request;
use Overtrue\LaravelWeChat\Model\WechatUserInfoRepository;

class UserInfoController extends \Illuminate\Routing\Controller
{

    public function info(Request $request, WechatUserInfoRepository $wechatUserInfoRepository)
    {

        return $wechatUserInfoRepository->getWechatUserInfo($request->uuid, $request->openid);
    }

}
