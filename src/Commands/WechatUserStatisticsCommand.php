<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Overtrue\LaravelWeChat\Commands;


use Illuminate\Console\Command;
use Overtrue\LaravelWeChat\Domain\WechatStatistics\WechatUserCumulateUsecase;
use Overtrue\LaravelWeChat\Domain\WechatStatistics\WechatUserSummaryUsecase;

class WechatUserStatisticsCommand extends Command
{


    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'wechat:user_statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '获取微信的统计数据';

    /**
     * Install directory.
     *
     * @var string
     */
    protected $directory = '';
    /**
     * @var WechatUserSummaryUsecase
     */
    private $wechatUserStatisticsUsecase;
    /**
     * @var WechatUserCumulateUsecase
     */
    private $wechatUserCumulateUsecase;


    /**
     *
     * /**
     * ParkRecordCheckCommand constructor.
     *
     * @param WechatUserSummaryUsecase  $wechatUserStatisticsUsecase
     * @param WechatUserCumulateUsecase $wechatUserCumulateUsecase
     */
    public function __construct(
        WechatUserSummaryUsecase $wechatUserStatisticsUsecase,
        WechatUserCumulateUsecase $wechatUserCumulateUsecase
    ) {
        parent::__construct();
        $this->wechatUserStatisticsUsecase = $wechatUserStatisticsUsecase;
        $this->wechatUserCumulateUsecase = $wechatUserCumulateUsecase;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->wechatUserStatisticsUsecase->handle();
        $this->wechatUserCumulateUsecase->handle();
    }


}
