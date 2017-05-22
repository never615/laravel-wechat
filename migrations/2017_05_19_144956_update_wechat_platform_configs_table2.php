<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateWechatUserInfoTable
 */
class UpdateWechatPlatformConfigsTable2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wechat_platform_configs', function (Blueprint $table) {
            $table->string("component_verify_ticket")->nullable()->change();


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
            $table->string("component_verify_ticket")->change();
        });

    }
}
