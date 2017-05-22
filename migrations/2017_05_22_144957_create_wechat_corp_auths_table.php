<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 企业微信第三方应用套件授权信息记录
 * Class CreateWechatCorpAuthsTable
 */
class CreateWechatCorpAuthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_corp_auths', function (Blueprint $table) {
            $table->increments('id');
            $table->string("permanent_code");
            $table->string("corp_id")->unique();
            $table->unsignedInteger("uuid")->unique()->nullable()->comment("分配的商城id");
            $table->string('corp_name');
            $table->json("auth_info");
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
        Schema::dropIfExists('wechat_corp_auths');
    }
}
