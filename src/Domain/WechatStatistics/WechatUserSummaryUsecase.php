<?php
/**
 * Created by PhpStorm.
 * User: never615 <never615.com>
 * Date: 02/11/2017
 * Time: 5:43 PM
 */

namespace Overtrue\LaravelWeChat\Domain\WechatStatistics;

use Carbon\Carbon;
use EasyWeChat\Factory;
use Overtrue\LaravelWeChat\Model\WechatAuthInfo;
use Overtrue\LaravelWeChat\Model\WechatUserSummary;
use Overtrue\LaravelWeChat\WechatUtils;


/**
 *
 * 微信用户增减数据
 *
 * 检查自有数据库最新的用户数据是从什么时候开始的,
 * 如果没有数据,则从2014年开始拉取微信用户统计数据.有的话,则拉取没有的数据.
 *
 * 微信公众号使用
 *
 * Class WechatUserStatisticsUsecase
 *
 * @package Overtrue\LaravelWeChat\Domain
 */
class WechatUserSummaryUsecase
{
    /**
     * @var WechatUtils
     */
    private $wechatUtils;


    /**
     * WechatUserStatisticsUsecase constructor.
     *
     * @param WechatUtils $wechatUtils
     */
    public function __construct(WechatUtils $wechatUtils)
    {
        $this->wechatUtils = $wechatUtils;
    }

    //接口侧的公众号数据的数据库中仅存储了 2014年12月1日之后的数据，
    //将查询不到在此之前的日期，即使有查到，也是不可信的脏数据；

    public function handle()
    {
        $openPlatform = \EasyWeChat::openPlatform(); // 开放平台

        WechatAuthInfo::whereNotNull("uuid")
            ->whereNotNull("authorizer_refresh_token")
            ->whereNotNull("authorizer_appid")
            ->chunk(10, function ($authInfos) use ($openPlatform) {
                foreach ($authInfos as $authInfo) {
                    $app = $openPlatform->officialAccount($authInfo->authorizer_appid,
                        $authInfo->authorizer_refresh_token);

                    //$from   示例： `2014-02-13` 获取数据的起始日期
                    //$to     示例： `2014-02-18` 获取数据的结束日期，`$to`允许设置的最大值为昨日

                    //`$from` 和 `$to` 的差值需小于 “最大时间跨度”（比如最大时间跨度为 1 时，`$from` 和 `$to` 的差值只能为 0，才能小于 1 ），否则会报错
                    $from = '2017-01-01';
//                    $from = '2018-08-01';
                    $to = Carbon::yesterday()->toDateString();

                    //1. 获取当前已有统计数据统计到了那一天
                    $lastWechatUserSummary = WechatUserSummary::orderBy("ref_date", 'desc')
                        ->where("uuid", $authInfo->uuid)
                        ->where("appid", $authInfo->authorizer_appid)
                        ->first();

                    if ($lastWechatUserSummary) {
                        $lastStatisticsRefDate = $lastWechatUserSummary->ref_date;
                        //如果上一次统计的时间是昨天,则不继续进行获取统计数据
                        if ($lastStatisticsRefDate == $to) {
                            return;
                        } else {
                            //如果最近一天的统计数据不是昨天,则从最新一天的统计数据的第二天开始继续获取统计数据
                            $from = Carbon::createFromFormat('Y-m-d', $lastStatisticsRefDate)->addDay()->toDateString();
                        }
                    }


                    while (Carbon::createFromFormat('Y-m-d', $from)->addDay(6)->toDateString() < $to) {
                        //如果开始和技术间隔大于七天,则直接从开始日期往后查询七天
                        $tempFrom = $from;
                        $tempTo = Carbon::createFromFormat('Y-m-d', $from)->addDay(6)->toDateString();
                        $this->getData($authInfo, $app, $tempFrom, $tempTo);

                        //重置开始时间
                        $from = Carbon::createFromFormat('Y-m-d', $tempTo)->addDay(1)->toDateString();
                    }

                    $this->getData($authInfo, $app, $from, $to);
                }
            });
    }


    /**
     *
     * @param      $authInfo
     * @param      $app
     * @param      $from
     * @param      $to
     * @return mixed
     */
    private function getData($authInfo, $app, $from, $to)
    {
        $userSummary = $app->data_cube->userSummary($from, $to);
        $userSummary = $userSummary['list'];

        foreach ($userSummary as $item) {
            WechatUserSummary::create([
                "uuid"        => $authInfo->uuid,
                "appid"       => $authInfo->authorizer_appid,
                "ref_date"    => $item['ref_date'],
                "user_source" => $item["user_source"],
                'new_user'    => $item['new_user'],
                'cancel_user' => $item['cancel_user'],
            ]);
        }
    }


}
