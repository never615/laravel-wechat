<?php

namespace Overtrue\LaravelWechat\Model;


use Illuminate\Database\Eloquent\Model;


class WechatPlatformConfig extends Model
{


    protected $guarded = [

    ];

    protected $casts = [
        "suite_ticket" => "array",
        "permanent_code"=>"array"
    ];


}
