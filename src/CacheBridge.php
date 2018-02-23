<?php

namespace Overtrue\LaravelWechat;

use Illuminate\Cache\Repository;
use Overtrue\LaravelWechat\Model\WechatPlatformConfig;
use Psr\SimpleCache\CacheInterface;

/**
 * 在缓存中把开放平台的ticket存储到数据库中,以避免缓存清除,微信服务不可用
 *
 * Cache bridge for laravel.
 */
class CacheBridge implements CacheInterface
{
    //在缓存中把开放平台的ticket存储到数据库中,以避免缓存清除,微信服务不可用
    const COMPONENT_VERIFY_TICKET = 'easywechat.open_platform.verify_ticket.';


    /**
     * @var \Illuminate\Cache\Repository
     */
    protected $repository;

    /**
     * @param \Illuminate\Cache\Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function get($key, $default = null)
    {
        $value = $this->repository->get($key, $default);
        if ($value) {
            return $value;
        } else {
            if (strpos($key, self::COMPONENT_VERIFY_TICKET) === 0) {
                $config = WechatPlatformConfig::first();
                if ($config) {
                    return $config->component_verify_ticket;
                } else {
                    return $value;
                }
            } else {
                return $value;
            }
        }
    }

    public function set($key, $value, $ttl = null)
    {
        if (strpos($key, self::COMPONENT_VERIFY_TICKET) === 0) {
            //在保存ticket,为了保证安全,在数据库在保存一份
            $platformConfig = WechatPlatformConfig::first();
            if ($platformConfig) {
                $platformConfig->component_verify_ticket = $value;
                $platformConfig->save();
            } else {
                WechatPlatformConfig::create([
                    "component_verify_ticket" => $value,
                ]);
            }
        }

        return $this->repository->put($key, $value, $this->toMinutes($ttl));
    }

    public function delete($key)
    {
    }

    public function clear()
    {
    }

    public function getMultiple($keys, $default = null)
    {
    }

    public function setMultiple($values, $ttl = null)
    {
    }

    public function deleteMultiple($keys)
    {
    }

    public function has($key)
    {
        return $this->repository->has($key);
    }

    protected function toMinutes($ttl = null)
    {
        if (!is_null($ttl)) {
            return $ttl / 60;
        }
    }

}
