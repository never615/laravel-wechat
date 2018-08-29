<?php

namespace Overtrue\LaravelWeChat\Controllers\Api;


use Illuminate\Http\Request;
use Mallto\Admin\SubjectUtils;
use Mallto\Tool\Exception\ResourceException;
use Overtrue\LaravelWeChat\Model\WechatUserCumulate;

class WechatUserStatisticController extends \Illuminate\Routing\Controller
{

    public function cumulate(Request $request)
    {


        $uuid = SubjectUtils::getUUID();


        $count = WechatUserCumulate::where('uuid', $uuid)
            ->count();
        if ($count === 0) {
            throw new ResourceException("该uuid下没有记录");
        }

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
