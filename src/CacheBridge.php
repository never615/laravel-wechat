<?php

namespace Overtrue\LaravelWechat;

use Doctrine\Common\Cache\Cache as CacheInterface;
use Illuminate\Support\Facades\Cache;
use Overtrue\LaravelWechat\Model\WechatPlatformConfig;

/**
 * Cache bridge for laravel.
 */
class CacheBridge implements CacheInterface
{

    const COMPONENT_VERIFY_TICKET = 'easywechat.open_platform.component_verify_ticket.';
    const SUIT_TICKET = 'easywechat.corp_server.suite_ticket.';
    const PERMANENT_CODE = 'easywechat.corp_server.permanent_code.';

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     *
     * @return mixed The cached data or FALSE, if no cache entry exists for the given id.
     */
    public function fetch($id)
    {
        $data = Cache::get($id);
        if ($data) {
            return $data;
        } else {
            if (strpos($id, self::COMPONENT_VERIFY_TICKET) === 0) {
                $config = WechatPlatformConfig::first();
                if ($config) {
                    return $config->component_verify_ticket;
                } else {
                    return $data;
                }
            } elseif (strpos($id, self::SUIT_TICKET) === 0) {
                $config = WechatPlatformConfig::first();
                if ($config) {
                    $appId = str_replace(self::SUIT_TICKET, "", $id);

                    return $config->suite_ticket["$appId"];
                } else {
                    return $data;
                }
            } elseif (strpos($id, self::PERMANENT_CODE) === 0) {
                $config = WechatPlatformConfig::first();
                if ($config) {
                    $appId = str_replace(self::SUIT_TICKET, "", $id);

                    return $config->permanent_code["$appId"];
                } else {
                    return $data;
                }
            } else {
                return $data;
            }
        }
    }

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id The cache id of the entry to check for.
     *
     * @return bool TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    public function contains($id)
    {
        return Cache::has($id);
    }

    /**
     * Puts data into the cache.
     *
     * If a cache entry with the given id already exists, its data will be replaced.
     *
     * @param string $id       The cache id.
     * @param mixed  $data     The cache entry/data.
     * @param int    $lifeTime The lifetime in number of seconds for this cache entry.
     *                         If zero (the default), the entry never expires (although it may be deleted from the cache
     *                         to make place for other entries).
     *
     * @return bool TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    public function save($id, $data, $lifeTime = 0)
    {
        if (strpos($id, self::COMPONENT_VERIFY_TICKET) === 0) {
            //在保存ticket,为了保证安全,在数据库在保存一份
            $platformConfig = WechatPlatformConfig::first();
            if ($platformConfig) {
                $platformConfig->component_verify_ticket = $data;
                $platformConfig->save();
            } else {
                WechatPlatformConfig::create([
                    "component_verify_ticket" => $data,
                ]);
            }
        } elseif (strpos($id, self::SUIT_TICKET) === 0) {
            $appId = str_replace(self::SUIT_TICKET, "", $id);


            //在保存ticket,为了保证安全,在数据库在保存一份
            $platformConfig = WechatPlatformConfig::first();
            if ($platformConfig) {
                $suiteTicke = $platformConfig->suite_ticket;
                $suiteTicke[$appId] = $data;
                $platformConfig->suite_ticket = $suiteTicke;
                $platformConfig->save();
            } else {
                WechatPlatformConfig::create([
                    "suite_ticket" => [$appId => $data],
                ]);
            }
        } elseif (strpos($id, self::PERMANENT_CODE) === 0) {
            $appId = str_replace(self::PERMANENT_CODE, "", $id);

            //在保存永久授权码,为了保证安全,在数据库在保存一份
            $platformConfig = WechatPlatformConfig::first();
            if ($platformConfig) {
                $permanentCode = $platformConfig->permanent_code;
                $permanentCode[$appId] = $data;
                $platformConfig->permanent_code = $permanentCode;
                $platformConfig->save();
            } else {
                WechatPlatformConfig::create([
                    "permanent_code" => [$appId => $data],
                ]);
            }
        }


        if ($lifeTime == 0) {
            return Cache::forever($id, $data);
        }

        return Cache::put($id, $data, $lifeTime / 60);
    }

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return bool TRUE if the cache entry was successfully deleted, FALSE otherwise.
     *              Deleting a non-existing entry is considered successful.
     */
    public function delete($id)
    {
        return Cache::forget($id);
    }

    /**
     * Retrieves cached information from the data store.
     *
     * @return array|null An associative array with server's statistics if available, NULL otherwise.
     */
    public function getStats()
    {
    }
}
