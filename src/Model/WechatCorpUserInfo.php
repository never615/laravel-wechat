<?php

namespace Overtrue\LaravelWechat\Model;


use Illuminate\Database\Eloquent\Model;


class WechatCorpUserInfo extends Model
{
    protected $connection = 'wechat_public';

    protected $guarded = [

    ];

    protected $casts = [
        'department' => "array",
    ];


    public function auth()
    {
        return $this->belongsTo(WechatCorpAuth::class);
    }
}
