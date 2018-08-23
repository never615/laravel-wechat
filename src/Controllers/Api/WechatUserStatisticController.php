<?php

namespace Overtrue\LaravelWeChat\Controllers\Api;


use Illuminate\Http\Request;
use Mallto\Admin\SubjectUtils;
use Overtrue\LaravelWeChat\Model\WechatUserCumulate;

class WechatUserStatisticController extends \Illuminate\Routing\Controller
{

    public function cumulate(Request $request)
    {


        $uuid = SubjectUtils::getUUID();

        $result = WechatUserCumulate::where('uuid', $uuid)
            ->where('ref_date', ">=", $request->from)
            ->where('ref_date', "<=", $request->to)
            ->where('type', $request->type)
            ->orderBy('ref_date')
            ->select('ref_date', 'cumulate_user', 'new_user', 'type')
            ->get();


        return $result;
    }

}
