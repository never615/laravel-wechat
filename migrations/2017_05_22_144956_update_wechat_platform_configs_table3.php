<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateWechatUserInfoTable
 */
class UpdateWechatPlatformConfigsTable3 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wechat_platform_configs', function (Blueprint $table) {
            $table->json("permanent_code")->nullable()->comment("企业号应用的永久授权码");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wechat_platform_configs', function (Blueprint $table) {
            $table->dropColumn('permanent_code');
        });

    }
}
