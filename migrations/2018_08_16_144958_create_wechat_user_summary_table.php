<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 微信用户增减数据
 * 获取自微信接口
 * Class CreateWechatUserStatistics
 */
class CreateWechatUserSummaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_user_summaries', function (Blueprint $table) {
            $table->increments('id');
            $table->string("uuid");
            $table->string("appid");
            $table->date("ref_date");
            $table->string("user_source")->comment("用户的渠道，数值代表的含义如下： 0代表其他合计 1代表公众号搜索 17代表名片分享 30代表扫描二维码 43代表图文页右上角菜单 51代表支付后关注（在支付完成页） 57代表图文页内公众号名称 75代表公众号文章广告 78代表朋友圈广告");
            $table->integer("new_user")->comment("新增的用户数量");
            $table->integer("cancel_user")->comment("取消关注的用户数量，new_user减去cancel_user即为净增用户数量");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wechat_user_summaries');
    }
}
