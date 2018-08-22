<?php
/**
 * Created by PhpStorm.
 * User: never615 <never615.com>
 * Date: 02/11/2017
 * Time: 5:43 PM
 */

namespace Overtrue\LaravelWeChat\Domain\WechatStatistics;

use Carbon\Carbon;
use Mallto\Tool\Exception\ResourceException;
use Overtrue\LaravelWeChat\Model\WechatAuthInfo;
use Overtrue\LaravelWeChat\Model\WechatUserCumulate;
use Overtrue\LaravelWeChat\WechatUtils;


/**
 * 微信累计用户数据
 * Class WechatUserCumulateUsecase
 *
 * @package Overtrue\LaravelWeChat\Domain\WechatStatistics
 */
class WechatUserCumulateUsecase
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
        WechatAuthInfo::whereNotNull("uuid")
            ->whereNotNull("authorizer_refresh_token")
            ->whereNotNull("authorizer_appid")
            ->chunk(10, function ($authInfos) {
                foreach ($authInfos as $authInfo) {
                    $openPlatform = \EasyWeChat::openPlatform(); // 开放平台

                    $app = $openPlatform->officialAccount($authInfo->authorizer_appid,
                        $authInfo->authorizer_refresh_token);

                    //$from   示例： `2014-02-13` 获取数据的起始日期
                    //$to     示例： `2014-02-18` 获取数据的结束日期，`$to`允许设置的最大值为昨日

                    //`$from` 和 `$to` 的差值需小于 “最大时间跨度”（比如最大时间跨度为 1 时，`$from` 和 `$to` 的差值只能为 0，才能小于 1 ），否则会报错
                    $from = '2015-12-01';
//                    $from = '2018-08-01';
                    $to = Carbon::yesterday()->toDateString();
//                    $to = '2015-01-01';

                    //1. 获取当前已有统计数据统计到了那一天
                    $lastWechatUserCumulate = WechatUserCumulate::orderBy("ref_date", 'desc')
                        ->where("uuid", $authInfo->uuid)
                        ->where("appid", $authInfo->authorizer_appid)
                        ->first();


                    //上一次统计的累计用户数量
                    $lastCumulate = 0;

                    if ($lastWechatUserCumulate) {
                        $lastStatisticsRefDate = $lastWechatUserCumulate->ref_date;
                        //如果上一次统计的时间是昨天,则不继续进行获取统计数据
                        if ($lastStatisticsRefDate == $to) {
                            return;
                        } else {
                            //如果最近一天的统计数据不是昨天,则从最新一天的统计数据的第二天开始继续获取统计数据
                            $from = Carbon::createFromFormat('Y-m-d', $lastStatisticsRefDate)
                                ->addDay()->toDateString();
                        }

                        $lastCumulate = $lastWechatUserCumulate->cumulate_user;
                    }


                    while (Carbon::createFromFormat('Y-m-d', $from)->addDay(6)->toDateString() < $to) {
                        //如果开始和技术间隔大于七天,则直接从开始日期往后查询七天
                        $tempFrom = $from;
                        $tempTo = Carbon::createFromFormat('Y-m-d', $from)->addDay(6)->toDateString();
                        $lastCumulate = $this->getData($authInfo, $app, $tempFrom, $tempTo, $lastCumulate);

                        //重置开始时间
                        $from = Carbon::createFromFormat('Y-m-d', $tempTo)->addDay(1)->toDateString();
                    }

                    $this->getData($authInfo, $app, $from, $to, $lastCumulate);


                    //处理年度和月度累计用户数据

                }
            });


    }


    /**
     * 从微信获取用户累计数据
     *
     * @param      $authInfo
     * @param      $app
     * @param      $from
     * @param      $to
     * @return mixed
     */
    private function getData($authInfo, $app, $from, $to, $lastCumulate)
    {
        $userCumulate = $app->data_cube->userCumulate($from, $to);
        if (isset($userCumulate['list'])) {
            $userCumulate = $userCumulate['list'];


            foreach ($userCumulate as $item) {
                $newUser = null;
                if ($lastCumulate) {
                    $newUser = $item['cumulate_user'] - $lastCumulate;
                }
                $lastCumulate = $item['cumulate_user'];

                $this->createCumulate($authInfo, $item, $newUser);
            }

            return $lastCumulate;
        }else{
            \Log::error("请求微信统计数据失败");
            throw new ResourceException("请求微信数据失败");
        }
    }


    /**
     * 添加一天的累计数据
     *
     * @param $authInfo
     * @param $item
     * @param $newUser
     */
    private function createCumulate($authInfo, $item, $newUser)
    {
        WechatUserCumulate::create([
            "uuid"          => $authInfo->uuid,
            "appid"         => $authInfo->authorizer_appid,
            "ref_date"      => $item["ref_date"],
            'cumulate_user' => $item['cumulate_user'],
            'new_user'      => $newUser,
            'type'          => 'day',
        ]);

        $date = Carbon::createFromFormat("Y-m-d", $item["ref_date"]);
        //如果ref_date是月度的最后一天,则创建一条月度统计数据
        if ($date->isLastOfMonth()) {
            //计算相比上月新增了多少用户
            $lastMonthData = WechatUserCumulate::where('type', 'month')
                ->where("uuid", $authInfo->uuid)
                ->where('ref_date', $date->copy()->addMonths(-1)
                    ->endOfMonth()
                    ->format("Y-m"))
                ->first();
            if ($lastMonthData) {
                $newUser = $item['cumulate_user'] - $lastMonthData->cumulate_user;
            } else {
                $newUser = null;
            }

            WechatUserCumulate::create([
                "uuid"          => $authInfo->uuid,
                "appid"         => $authInfo->authorizer_appid,
                "ref_date"      => $date->format("Y-m"),
                'cumulate_user' => $item['cumulate_user'],
                'new_user'      => $newUser,
                'type'          => 'month',
            ]);
        }


        //如果ref_date是年度的最后一天,则创建一条年度统计数据
        if ($date->format("Y-m-d") == ($date->lastOfYear()->format("Y-m-d"))) {
            //计算相比上年新增了多少用户
            $lastYearData = WechatUserCumulate::where('type', 'year')
                ->where("uuid", $authInfo->uuid)
                ->where('ref_date', $date->copy()
                    ->addYears(-1)
                    ->lastOfYear()
                    ->format("Y"))
                ->first();
            if ($lastYearData) {
                $newUser = $item['cumulate_user'] - $lastYearData->cumulate_user;
            } else {
                $newUser = $item['cumulate_user'];
            }
            WechatUserCumulate::create([
                "uuid"          => $authInfo->uuid,
                "appid"         => $authInfo->authorizer_appid,
                "ref_date"      => $date->format("Y"),
                'cumulate_user' => $item['cumulate_user'],
                'new_user'      => $newUser,
                'type'          => 'year',
            ]);
        }
    }


}
