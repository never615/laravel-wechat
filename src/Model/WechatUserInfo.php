<?php

namespace Overtrue\LaravelWechat\Model;


use Illuminate\Database\Eloquent\Model;


class WechatUserInfo extends Model
{

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'connection-name';

    protected $guarded = [

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
    
    public function auth(){
        return $this->belongsTo(WechatAuthInfo::class);
    }
}
