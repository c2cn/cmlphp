<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  2.5
 * cml框架 memcache缓存驱动
 * *********************************************************** */
namespace Cml\Cache;

use Cml\Config;
use Cml\Lang;

class Memcache extends namespace\Base
{
    /**
    * @var bool|array
    */
    private $conf;

    /**
    * @var \Memcache | \Memcached
    */
    private $memcache;

    /**
     * @param bool $conf
     */
    public function __construct($conf = false)
    {
        $this->conf = $conf ? $conf : Config::get('CACHE');

        if (extension_loaded('Memcached')) {
            $this->memcache = new \Memcached;
        } elseif (extension_loaded('Memcache')) {
            $this->memcache = new \Memcache;
        } else {
            \Cml\throwException(Lang::get('_CACHE_EXTEND_NOT_INSTALL_', 'Memcache'));
        }

        if (!$this->memcache) {
            \Cml\throwException(Lang::get('_CACHE_NEW_INSTANCE_ERROR_', 'Memcache'));
        }

        if (count($this->conf['server']) > 1) { //单台
            if (!$this->memcache->connect($this->conf['host'], $this->conf['port'])) {
                \Cml\throwException(Lang::get('_CACHE_CONNECT_FAIL_', 'Memcache',
                    $this->conf['host'] . ':' . $this->conf['port']
                ));
            }
        } else { //多台
            foreach ($this->conf['server'] as $val) {
                $this->memcache->addServer($val['host'], $val['port']); //增加服务器
            }
        }

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
        $return = json_decode($this->memcache->get($this->conf['prefix'] . $key), true);
        is_null($return) && $return = false;
        return $return; //orm层做判断用
    }

    /**
     * 存储对象
     *
     * @param mixed $key
     * @param mixed $value
     * @param int $expire
     *
     * @return bool
     */
    public function set($key, $value, $expire = 0)
    {
        return $this->memcache->set($this->conf['prefix'] . $key, json_encode($value, PHP_VERSION >= '5.4.0' ? JSON_UNESCAPED_UNICODE : 0), false, $expire);
    }

    /**
     * 更新对象
     *
     * @param mixed $key
     * @param mixed $value
     * @param int $expire
     *
     * @return bool
     */
    public function update($key, $value, $expire = 0)
    {
        $this->memcache->replace($this->conf['prefix'] . $key, json_encode($value, PHP_VERSION >= '5.4.0' ? JSON_UNESCAPED_UNICODE : 0), false, $expire);
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
        return $this->memcache->delete($this->conf['prefix'] . $key);
    }

    /**
     * 清洗已经存储的所有元素
     *
     */
    public function truncate()
    {
        $this->memcache->flush();
        return true;
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
        $this->memcache->increment($this->conf['prefix'] . $key, $val);
    }

    /**
     * 自减
     *
     * @param mixed $key
     * @param int $val
     *
     * @return bool
     */
    public function decrement($key, $val = 1)
    {
        $this->memcache->decrement($this->conf['prefix'] . $key, $val);
    }

    /**
     * 返回实例便于操作未封装的方法
     *
     * @return \Memcache|\Memcached
     */
    public function getInstance($key = '')
    {
        return $this->memcache;
    }
}