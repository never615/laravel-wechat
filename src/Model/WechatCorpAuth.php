<?php

namespace Overtrue\LaravelWechat\Model;


use Illuminate\Database\Eloquent\Model;


class WechatCorpAuth extends Model
{

    protected $guarded = [

    ];
    
    protected $casts=[
        'auth_info'=>'array'
    ];


    public function users()
    {
        return $this->hasMany(WechatCorpUserInfo::class);
    }

}
