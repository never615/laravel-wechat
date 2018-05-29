<?php

namespace Overtrue\LaravelWeChat\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mallto\Admin\SubjectUtils;
use Mallto\Tool\Exception\ResourceException;
use Mallto\Tool\Utils\SignUtils;
use Overtrue\LaravelWeChat\WechatUtils;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class ShareAroundController extends Controller
{


    private $shakearound;

    /**
     * ShareAroundController constructor.
     *
     * @param WechatUtils $wechatUtils
     */
    public function __construct(WechatUtils $wechatUtils)
    {
        $uuid = SubjectUtils::getUUID();

        $officalAccount = $wechatUtils->createAppFromOpenPlatform2($uuid);
        $this->shakearound = $officalAccount->shake_around;
    }

    public function createGroup(Request $request)
    {
        $this->validate($request, [
            "group_name" => "required",
        ]);

        $groupName = $request->get("group_name");

        /* 返回结果
        {
          "data": {
              "group_id" : 123,
              "group_name" : "test"
          },
          "errcode": 0,
          "errmsg": "success."
        }
        */
        $result = $this->shakearound->group->create($groupName);

        $result = $this->responseHandler($result);

        return $result['data'];
    }

    public function groupDetail($groupId, Request $request)
    {
//        $this->validate($request, [
//            "group_id" => "required",
//        ]);

//        $groupId = $request->get("group_id");

        if (is_null($groupId)) {
            throw new ResourceException("group id is null");
        }

        /* 返回结果
        {
            "data": {
                "group_id" : 123,
                "group_name" : "test",
                "total_count": 100,
                "devices" :[
                    {
                        "device_id" : 123456,
                        "uuid" : "FDA50693-A4E2-4FB1-AFCF-C6EB07647825",
                        "major" : 10001,
                        "minor" : 10001,
                        "comment" : "test device1",
                        "poi_id" : 12345,
                    },
                    {
                        "device_id" : 123457,
                        "uuid" : "FDA50693-A4E2-4FB1-AFCF-C6EB07647825",
                        "major" : 10001,
                        "minor" : 10002,
                        "comment" : "test device2",
                        "poi_id" : 12345,
                    }
                ]
            },
            "errcode": 0,
            "errmsg": "success."
        }
        */

        $page = $request->get("page", 1);
        $count = 20;
        $begin = ($page - 1) * $count;
        $result = $this->shakearound->group->get($groupId, $begin, $count);
        $result = $this->responseHandler($result);

        return $result["data"];
    }

    /**
     * 添加设备到分组
     * 每个分组能够持有的设备上限为10000，并且每次添加操作的添加上限为1000。
     *
     * @param $request
     * @return mixed
     */
    public function addDevices($groupId, Request $request)
    {
        /*devices_ids示例
        [
            [
                'uuid' => 'FDA50693-A4E2-4FB1-AFCF-C6EB07647825',
                'major' => 10001,
                'minor' => 12102,
            ],
            [
                'uuid' => 'FDA50693-A4E2-4FB1-AFCF-C6EB07647825',
                'major' => 10001,
                'minor' => 12103,
            ]
        ]
        */

        if (is_null($groupId)) {
            throw new ResourceException("group id is null");
        }

        $this->validate($request, [
            "device_ids" => "required",
        ]);

        $deviceIds = $request->get("device_ids");
        if (!is_array($deviceIds)) {
            $deviceIds = json_decode($deviceIds, true);
        }
        /* 返回结果
        {
            "data": {
            },
            "errcode": 0,
            "errmsg": "success."
        }
        */
        $result = $this->shakearound->group->addDevices($groupId, $deviceIds);

        $result = $this->responseHandler($result);

        return response()->nocontent();
    }


    /**
     * 从分组中移除设备信息
     *
     * @param $request
     */
    public function removeDevices($groupId, Request $request)
    {
        if (is_null($groupId)) {
            throw new ResourceException("group id is null");
        }
        $this->validate($request, [
            "device_ids" => "required",
        ]);

        $deviceIds = $request->get("device_ids");
        if (!is_array($deviceIds)) {
            $deviceIds = \Qiniu\json_decode($deviceIds, true);
        }

        $result = $this->shakearound->group->removeDevices($groupId, $deviceIds);
        $result = $this->responseHandler($result);

        return response()->nocontent();
    }


    private function responseHandler($response)
    {
        if ($response["errcode"] != 0) {
            throw new ResourceException($response["errmsg"]);
        }

        return $response;
    }


}
