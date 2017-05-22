<?php

namespace Overtrue\LaravelWechat\Model;


use Illuminate\Database\Eloquent\Model;


class WechatUserInfo extends Model
{
    protected $guarded = [

    ];

    
    public function auth(){
        return $this->belongsTo(WechatAuthInfo::class);
    }
}
