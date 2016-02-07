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
     * @var int 类型 1Memcached 2 Memcache
     */
    private $type = 1;

    /**
     * 返回memcache驱动类型  加锁时用
     *
     * @return int
     */
    public function getDriverType()
    {
        return $this->type;
    }

    /**
     * @param bool $conf
     */
    public function __construct($conf = false)
    {
        $this->conf = $conf ? $conf : Config::get('CACHE');

        if (extension_loaded('Memcached')) {
            $this->memcache = new \Memcached('cml_memcache_pool');
            $this->type = 1;
        } elseif (extension_loaded('Memcache')) {
            $this->memcache = new \Memcache;
            $this->type = 2;
        } else {
            \Cml\throwException(Lang::get('_CACHE_EXTEND_NOT_INSTALL_', 'Memcached/Memcache'));
        }

        if (!$this->memcache) {
            \Cml\throwException(Lang::get('_CACHE_NEW_INSTANCE_ERROR_', 'Memcache'));
        }

        if ($this->type == 2) {//memcache
            foreach ($this->conf['server'] as $val) {
                if (!$this->memcache->addServer($val['host'], $val['port'])) {
                    \Cml\throwException(Lang::get('_CACHE_CONNECT_FAIL_', 'Memcache',
                        $this->conf['host'] . ':' . $this->conf['port']
                    ));
                }
            }
            return;
        }

        if (md5(json_encode($this->conf['server'])) !== md5(json_encode($this->memcache->getServerList()))) {
            $this->memcache->quit();
            $this->memcache->resetServerList();
            $this->memcache->setOption(\Memcached::OPT_PREFIX_KEY, $this->conf['prefix']);
            \Memcached::HAVE_JSON  && $this->memcache->setOption(\Memcached::OPT_SERIALIZER, \Memcached::SERIALIZER_JSON_ARRAY);
            if (!$this->memcache->addServers(array_values($this->conf['server']))) {
                \Cml\throwException(
                    Lang::get('_CACHE_CONNECT_FAIL_', 'Memcache',
                        json_encode($this->conf['server'])
                    ));
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
        if ($this->type === 1) {
            $return = $this->memcache->get($key);
        } else {
            $return = json_decode($this->memcache->get($this->conf['prefix'] . $key), true);
        }

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
        if ($this->type === 1) {
            return $this->memcache->set($key, $value, $expire);
        } else {
            return $this->memcache->set($this->conf['prefix'] . $key, json_encode($value, PHP_VERSION >= '5.4.0' ? JSON_UNESCAPED_UNICODE : 0), false, $expire);
        }
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
        if ($this->type === 1) {
            return $this->memcache->replace($key, $value, $expire);
        } else {
            return $this->memcache->replace($this->conf['prefix'] . $key, json_encode($value, PHP_VERSION >= '5.4.0' ? JSON_UNESCAPED_UNICODE : 0), false, $expire);
        }

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
        $this->type === 2 && $key = $this->conf['prefix'] . $key;
        return $this->memcache->delete($key);
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
        $this->type === 2 && $key = $this->conf['prefix'] . $key;
        $this->memcache->increment($key, $val);
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
        $this->type === 2 && $key = $this->conf['prefix'] . $key;
        $this->memcache->decrement($key, $val);
    }

    /**
     * 返回实例便于操作未封装的方法
     *
     * @param string $key
     *
     * @return \Memcache|\Memcached
     */
    public function getInstance($key = '')
    {
        return $this->memcache;
    }
}