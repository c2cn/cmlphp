<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  2.5
 * cml框架 Apc缓存驱动
 * *********************************************************** */
namespace Cml\Cache;

use Cml\Config;
use Cml\Lang;

class Apc extends namespace\Base
{
    public function __construct($conf = false)
    {
        if (!function_exists('apc_cache_info')) {
            \Cml\throwException(Lang::get('_CACHE_EXTENT_NOT_INSTALL_', 'Apc'));
        }
        $this->conf = $conf ? $conf : Config::get('CACHE');
    }

    /**
     * 根据key取值
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return apc_fetch($this->conf['prefix'] . $key);
    }

    /**
     * 存储对象
     *
     * @param mixed $key
     * @param $value
     * @param int $expire
     *
     * @return bool
     */
    public function set($key, $value, $expire = 0)
    {
        ($expire == 0) && $expire = null;
        return apc_store($this->conf['prefix'] . $key, $value, $expire);
    }

    /**
     * 更新对象
     *
     * @param mixed $key
     * @param mixed $value
     * @param int $expire
     *
     * @return bool|int
     */
    public function update($key, $value, $expire = 0)
    {
        $arr = $this->get($key);
        if (!empty($arr)) {
            $arr = array_merge($arr, $value);
            return $this->set($key, $arr, $expire);
        }
        return 0;
    }

    /**
     * 删除对象
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function delete($key)
    {
        return apc_delete($this->conf['prefix'] . $key);
    }

    /**
     * 清洗已经存储的所有元素
     *
     */
    public function truncate()
    {
        return apc_clear_cache('user'); //只清除用户缓存
    }

    /**
     * 自增
     *
     * @param mixed $key
     * @param int $val
     *
     * @return bool
     */
    public function increment($key, $val = 1)
    {
        return apc_inc($this->conf['prefix'] . $key, (int)$val);
    }

    /**
     * 自减
     *
     * @param mixed $key
     * @param int $val
     *
     * @return bool
     */
    public function decrement($key, $val =1)
    {
        return apc_dec($this->conf['prefix'] . $key, (int)$val);
    }

    /**
     * 返回实例便于操作未封装的方法
     *
     * @return void
     */
    public function getInstance($key = '') {}
}