<?php

namespace Overtrue\LaravelWechat;

use Encore\Admin\Auth\Database\Traits\DynamicData;
use Illuminate\Database\Eloquent\Model;


class WechatPlatformConfig extends Model
{

    
    protected $guarded=[

    ];

    /**
     * WechatAuthInfo constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $connection = config('wechat.connection_name') ?: config('database.default');

        $this->setConnection($connection);
        parent::__construct($attributes);
    }

}
