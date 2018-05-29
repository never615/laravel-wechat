<?php

namespace Overtrue\LaravelWeChat\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mallto\Admin\SubjectUtils;
use Mallto\Tool\Exception\PermissionDeniedException;
use Mallto\Tool\Exception\ResourceException;
use Overtrue\LaravelWeChat\WechatUtils;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class OtherController extends Controller
{


    private $officalAccount;

    /**
     * ShareAroundController constructor.
     *
     * @param WechatUtils $wechatUtils
     */
    public function __construct(WechatUtils $wechatUtils)
    {
        $uuid = SubjectUtils::getUUID();

        $this->officalAccount = $wechatUtils->createAppFromOpenPlatform2($uuid);
    }


    public function url(Request $request)
    {
        $this->validate($request, [
            "action" => "required",
        ]);

        $action = $request->get("action");

        switch ($action) {
            case "shorten":
                return $this->shorten($request);
                break;
        }

        throw new PermissionDeniedException();
    }

    private function shorten(Request $request)
    {
        $this->validate($request, [
            "url" => "required",
        ]);
        $result = $this->officalAccount->url->shorten($request->get("url"));
        $result = $this->responseHandler($result);

        return response()->json([
            "short_url" => $result["short_url"],
        ]);
    }


    protected function responseHandler($response)
    {
        if ($response["errcode"] != 0) {
            throw new ResourceException($response["errmsg"]);
        }

        return $response;
    }


}
