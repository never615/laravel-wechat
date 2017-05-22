<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 记录微信用户信息
 * Class CreateWechatUserInfoTable
 */
class UpdateWechatUserInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wechat_user_infos', function (Blueprint $table) {
            $table->unsignedInteger("wechat_auth_info_id")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("wechat_user_infos", function ($table) {
                $table->dropColumn("wechat_auth_info_id");
            });
    }
}
