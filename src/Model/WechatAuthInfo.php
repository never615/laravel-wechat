<?php

namespace Overtrue\LaravelWechat;

use Encore\Admin\Auth\Database\Traits\DynamicData;
use Illuminate\Database\Eloquent\Model;


class WechatAuthInfo extends Model
{
    protected $fillable = [
        'authorizer_appid', 'authorizer_access_token', 'authorizer_refresh_token','nick_name','service_type_info',
        'verify_type_info','user_name','principal_name','business_info','alias','qrcode_url','func_info','authorization_code'
    ];

}
