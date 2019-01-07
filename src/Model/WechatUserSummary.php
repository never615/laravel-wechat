<?php

namespace Overtrue\LaravelWeChat\Model;


use Illuminate\Database\Eloquent\Model;


class WechatUserSummary extends Model
{
    public $timestamps = false;

    protected $connection = 'wechat_public';


    protected $guarded = [];


}
