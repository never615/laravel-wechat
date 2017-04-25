<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWechatAuthInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $connection = config('wechat.connection_name') ?: config('database.default');
        Schema::connection($connection)->create('wechat_auth_infos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger("uuid")->unique()->nullable()->comment("分配的商城id");
            $table->string('authorizer_appid')->commment('授权方appid');
            $table->string('authorizer_access_token')->commment('授权方接口调用凭据（在授权的公众号具备API权限时，才有此返回值），也简称为令牌');
            $table->string('authorizer_refresh_token')->commment('接口调用凭据刷新令牌（在授权的公众号具备API权限时，才有此返回值），刷新令牌主要用于公众号第三方平台获取和刷新已授权用户的access_token，只会在授权时刻提供，请妥善保存。 一旦丢失，只能让用户重新授权，才能再次拿到新的刷新令牌');
            $table->string('nick_name')->nullable()->commment('授权方昵称');
            $table->string('service_type_info')->nullable()->commment('授权方公众号类型，0代表订阅号，1代表由历史老帐号升级后的订阅号，2代表服务号');
            $table->string('verify_type_info')->nullable()->commment('授权方认证类型，-1代表未认证，0代表微信认证，1代表新浪微博认证，2代表腾讯微博认证，3代表已资质认证通过但还未通过名称认证，4代表已资质认证通过、还未通过名称认证，但通过了新浪微博认证，5代表已资质认证通过、还未通过名称认证，但通过了腾讯微博认证');
            $table->string('user_name')->nullable()->commment('授权方公众号的原始ID');
            $table->string('principal_name')->nullable()->commment('公众号的主体名称');
            $table->string('alias')->nullable()->commment('授权方公众号所设置的微信号，可能为空');
            $table->string('business_info')->nullable()->commment(' 用以了解以下功能的开通状况（0代表未开通，1代表已开通）：open_store:是否开通微信门店功能open_scan:是否开通微信扫商品功能open_pay:是否开通微信支付功能open_card:是否开通微信卡券功能open_shake:是否开通微信摇一摇功能');
            $table->string('qrcode_url')->nullable()->commment('二维码图片的URL，开发者最好自行也进行保存');
            $table->text('func_info')->nullable();
            $table->string('authorization_code')->nullable();
//公众号授权给开发者的权限集列表，ID为1到15时分别代表：
//消息管理权限
//用户管理权限
//帐号服务权限
//网页服务权限
//微信小店权限
//微信多客服权限
//群发与通知权限
//微信卡券权限
//微信扫一扫权限
//微信连WIFI权限
//素材管理权限
//微信摇周边权限
//微信门店权限
//微信支付权限
//自定义菜单权限

            $table->unique("authorizer_appid");
            $table->unique("uuid");


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

        Schema::connection($connection)->dropIfExists('wechat_auth_infos');
    }
}
