<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 15-1-25 下午3:07
 * @version  2.5
 * cml框架 锁驱动抽象类基类
 * *********************************************************** */
namespace Cml\Lock;

use Cml\Config;

abstract class Base
{
    /**
     * 保存锁数据
     *
     * @var array
     */
    protected static $lockCache = array();

    /**
     * 组装key
     *
     * @param string $key
     *
     * @return string
     */
    protected function getKey($key)
    {
        return Config::get('lock_prefix').$key;
    }

    /**
     * 上锁
     *
     * @param string $key
     * @param bool $wouldblock 是否堵塞
     *
     * @return mixed
     */
    abstract public function lock($key, $wouldblock = false);

    /**
     * 解锁
     *
     * @param string $key
     *
     * @return mixed
     */
    abstract public function unlock($key);

    /**
     * 定义析构函数 自动释放获得的锁
     */
   abstract public function __destruct();
}