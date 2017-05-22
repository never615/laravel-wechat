<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateWechatUserInfoTable
 */
class UpdateWechatPlatformConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wechat_platform_configs', function (Blueprint $table) {
            $table->json("suite_ticket");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wechat_platform_configs', function ($table) {
            $table->dropColumn("suite_ticket");

        });

    }
}
