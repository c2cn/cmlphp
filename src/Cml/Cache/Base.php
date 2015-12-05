<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  2.5
 * cml框架 缓存驱动抽象类基类
 * *********************************************************** */
namespace Cml\Cache;

abstract class Base
{

    public function __get($var)
    {
        return $this->get($var);
    }

    public function __set($key, $val)
    {
        return $this->set($key, $val);
    }

    abstract public function __construct($conf = false);

    /**
     * 根据key取值
     *
     * @param mixed $key
     *
     * @return mixed
     */
    abstract public function get($key);

    /**
     * 存储对象
     *
     * @param mixed $key
     * @param mixed $value
     * @param int $expire
     *
     * @return bool
     */
    abstract public function set($key, $value, $expire = 0);

    /**
     * 更新对象
     *
     * @param mixed $key
     * @param mixed $value
     * @param int $expire
     *
     * @return bool|int
     */
    abstract public function update($key, $value, $expire = 0);

    /**
     * 删除对象
     *
     * @param mixed $key
     *
     * @return bool
     */
    abstract public function delete($key);

    /**
     * 清洗已经存储的所有元素
     *
     * @return bool
     */
    abstract public function truncate();

    /**
     * 自增
     *
     * @param mixed $key
     * @param int $val
     *
     * @return bool
     */
    abstract public function increment($key, $val = 1);

    /**
     * 自减
     *
     * @param mixed $key
     * @param int $val
     *
     * @return bool
     */
    abstract public function decrement($key, $val = 1);

    /**
     * 返回实例便于操作未封装的方法
     *
     * @return \Redis | \Memcache | \Memcached
     */
    abstract public function getInstance($key = '');

}