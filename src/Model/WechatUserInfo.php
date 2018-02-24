<?php

namespace Overtrue\LaravelWeChat\Model;


use Illuminate\Database\Eloquent\Model;


class WechatUserInfo extends Model
{
    protected $connection = 'wechat_public';

    protected $guarded = [

    ];


    public function auth(){
        return $this->belongsTo(WechatAuthInfo::class);
    }
}
