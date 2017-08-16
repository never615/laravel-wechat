<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 企业微信第三方应用套件授权信息记录
 * Class CreateWechatCorpAuthsTable
 */
class UpdateWechatCorpAuthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wechat_corp_auths', function (Blueprint $table) {
            $table->json("contacts")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wechat_corp_auths', function (Blueprint $table) {
            $table->dropColumn("contacts");
        });
    }
}
