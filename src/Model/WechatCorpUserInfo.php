<?php

namespace Overtrue\LaravelWechat\Model;


use Illuminate\Database\Eloquent\Model;


class WechatCorpUserInfo extends Model
{
    protected $guarded = [

    ];

    
    public function auth(){
        return $this->belongsTo(WechatCorpAuth::class);
    }
}
