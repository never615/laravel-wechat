<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * 记录微信用户信息
 * Class CreateWechatUserInfoTable
 */
class CreateWechatUserInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_user_infos', function (Blueprint $table) {
            $table->increments('id');
            $table->string("openid");
            $table->string("nickname")->nullable();
            $table->string("sex")->nullable();
            $table->string("language")->nullable();
            $table->string("city")->nullable();
            $table->string("province")->nullable();
            $table->string("country")->nullable();
            $table->string("avatar")->nullable();
            $table->string("privilege")->nullable();
            $table->string("app_id")->nullable();

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
        Schema::dropIfExists('wechat_user_infos');
    }
}
