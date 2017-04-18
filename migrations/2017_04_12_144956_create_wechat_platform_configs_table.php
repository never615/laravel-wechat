<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        $connection = config('wechat.connection_name') ?: config('database.default');
        Schema::connection($connection)->create('wechat_platform_configs', function (Blueprint $table) {
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

        $connection = config('wechat.connection_name') ?: config('database.default');

        Schema::connection($connection)->dropIfExists('wechat_platform_configs');
    }
}
