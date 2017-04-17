<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * 记录微信用户信息
 * Class CreateWechatUserInfoTable
 */
class CreateWechatPlatformConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_platform_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->string("component_verify_ticket");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wechat_platform_configs');
    }
}
