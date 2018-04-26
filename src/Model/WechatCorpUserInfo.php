<?php

namespace Overtrue\LaravelWeChat\Model;


use Illuminate\Database\Eloquent\Model;


class WechatCorpUserInfo extends Model
{
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
