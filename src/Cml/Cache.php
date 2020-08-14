<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Cache处理类
 * *********************************************************** */

namespace Cml;

use Cml\Interfaces\Cache as CacheInst;

/**
 * Cache处理类
 *
 * @package Cml
 */
class Cache
{
    /**
     * 默认使用的配置
     *
     * @var string
     */
    protected static $conf = 'default_cache';

    /**
     * 获取cache实例
     *
     * @return CacheInst
     */
    protected static function getCache()
    {
        return Model::staticCache(self::$conf);
    }

    /**
     * 设置缓存使用的配置并返回cache实例
     *
     * @param string $conf
     *
     * @return CacheInst
     */
    public static function setCacheConfigAndGetCacheInst($conf = 'default_cache')
    {
        self::$conf = $conf;
        return static::getCache();
    }

    /**
     * 根据key取值
     *
     * @param string $key 要获取的缓存key
     *
     * @return mixed
     */
    public static function get($key)
    {
        return self::getCache()->get($key);
    }

    /**
     * 获取缓存，失败则调用回调函数并缓存结果
     *
     * @param $key
     * @param callable $callback
     * @param int $expire
     *
     * @return mixed
     */
    public static function getFailCacheCallback($key, callable $callback, $expire = 300)
    {
        $return = self::get($key);
        if (false === $return) {
            $return = call_user_func($callback);
            $return !== false && self::set($key, $return, $expire);
        }
        return $return;
    }

    /**
     * 存储对象
     *
     * @param mixed $key 要缓存的数据的key
     * @param mixed $value 要缓存的值,除resource类型外的数据类型
     * @param int $expire 缓存的有效时间 0为不过期
     *
     * @return bool
     */
    public static function set($key, $value, $expire = 0)
    {
        return self::getCache()->set($key, $value, $expire);
    }

    /**
     * 删除对象
     *
     * @param mixed $key 要删除的数据的key
     *
     * @return bool
     */
    public static function delete($key)
    {
        return self::getCache()->delete($key);
    }

    /**
     * 自增
     *
     * @param mixed $key 要自增的缓存的数据的key
     * @param int $val 自增的进步值,默认为1
     *
     * @return bool
     */
    public static function increment($key, $val = 1)
    {
        return self::getCache()->increment($key, $val);
    }

    /**
     * 自减
     *
     * @param mixed $key 要自减的缓存的数据的key
     * @param int $val 自减的进步值,默认为1
     *
     * @return bool
     */
    public static function decrement($key, $val = 1)
    {
        return self::getCache()->decrement($key, $val);
    }

    /**
     * 返回实例便于操作未封装的方法
     *
     * @param string $key
     *
     * @return \Memcache|\Memcached|\Redis
     */
    public static function getInstance($key = '')
    {
        return self::getCache()->getInstance($key);
    }
}
