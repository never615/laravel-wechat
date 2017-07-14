<?php

namespace Overtrue\LaravelWechat\Model;


use Illuminate\Database\Eloquent\Model;


class WechatPlatformConfig extends Model
{
    protected $connection = 'wechat_public';


    protected $guarded = [

    ];

    protected $casts = [
        "suite_ticket" => "array",
        "permanent_code"=>"array"
    ];


}
