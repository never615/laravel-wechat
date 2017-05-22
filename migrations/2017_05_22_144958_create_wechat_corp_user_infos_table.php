<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 记录企业微信用户信息
 * Class CreateWechatUserInfoTable
 */
class CreateWechatCorpUserInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_corp_user_infos', function (Blueprint $table) {
            $table->increments('id');
            $table->string("user_id");
            $table->string("name")->nullable();
            $table->string("gender")->nullable();
            $table->string("department")->nullable();
            $table->string("position")->nullable();
            $table->string("avatar")->nullable();
            $table->string("mobile")->nullable();
            $table->string("email")->nullable();
            $table->unsignedInteger("wechat_corp_auth_id")->nullable();
            $table->string("corp_id")->nullable();
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
        Schema::dropIfExists('wechat_corp_user_infos');
    }
}
