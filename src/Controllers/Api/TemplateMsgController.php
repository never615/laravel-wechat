<?php

namespace Overtrue\LaravelWeChat\Controllers\Api;

use EasyWeChat\Kernel\Exceptions\HttpException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Http\Request;
use Mallto\Admin\SubjectUtils;
use Mockery\Exception;
use Overtrue\LaravelWeChat\WechatUtils;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class TemplateMsgController extends \Illuminate\Routing\Controller
{

    /**
     * 发送微信模板消息
     *
     * @param Request     $request
     * @param WechatUtils $wechatUtils
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request, WechatUtils $wechatUtils)
    {
        $openPlatform = \EasyWeChat::openPlatform(); // 开放平台

        $uuid = SubjectUtils::getUUID();

        $officalAccount = $wechatUtils->createAppFromOpenPlatform($openPlatform, $uuid);
        $template_message = $officalAccount->template_message;

        try {

            $content = $template_message->send([
                'touser'      => $request->openid,
                'template_id' => $request->template_id,
                'url'         => $request->url,
                'data'        => json_decode($request->data, true),
            ]);

            if ($content['errcode'] == 0) {
                return response()->json([
                        "code" => 0,
                        'msg'  => $content['errmsg'],
                    ]
                );
            } else {
                return response()->json([
                        "code" => 1,
                        'msg'  => $content['errmsg'],
                    ]
                );
            }
        } catch (HttpException $exception) {

            \Log::error("模板消息发送失败1:".$exception->getMessage());
            \Log::warning($exception->getTraceAsString());

            return response()->json([
                    "code" => 0,
                    'msg'  => '模板消息发送失败',
                ]
            );
        } catch (ConnectException $exception) {
            \Log::error("微信模板消息发送异常2:".$exception->getMessage());

            return response()->json([
                    "code" => 0,
                    'msg'  => '模板消息发送失败',
                ]
            );
        } catch (Exception $exception) {
            \Log::error("微信模板消息发送异常3:".$exception->getMessage());

            return response()->json([
                    "code" => 0,
                    'msg'  => '模板消息发送失败',
                ]
            );
        }
    }
}
