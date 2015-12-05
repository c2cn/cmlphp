<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 15-1-25 下午3:07
 * @version  2.5
 * cml框架 锁Memcache驱动
 * *********************************************************** */

namespace Cml\Lock;

use Cml\Model;

class Memcache extends Base
{
    /**
     * 使用的缓存
     *
     * @var string
     */
    private $userCache = 'default_cache';

    public function __construct($userCache){
        is_null($userCache) || $this->userCache = $userCache;
    }

    /**
     * 上锁
     *
     * @param string $key
     * @param bool $wouldblock 是否堵塞
     *
     * @return mixed
     */
    public function lock($key, $wouldblock = false)
    {
        if(empty($key)) {
            return false;
        }
        $key = $this->getKey($key);

        if (Model::getInstance()->cache($this->userCache)->getInstance()->add($key, 1, 0)) {
            self::$lockCache[$key] = 1;
            return true;
        }

        //非堵塞模式
        if (!$wouldblock) {
            self::$lockCache[$key] = 0;
            return false;
        }

        //堵塞模式
        do {
            usleep(200);
        } while (!Model::getInstance()->cache($this->userCache)->getInstance()->add($key, 1, 0));

        self::$lockCache[$key] = 1;
        return true;
    }

    /**
     * 解锁
     *
     * @param string $key
     */
    public function unlock($key)
    {
        $key = $this->getKey($key);

        if (isset(self::$lockCache[$key]) && self::$lockCache[$key]) {
            Model::getInstance()->cache($this->userCache)->getInstance()->delete($key);
            unset(self::$lockCache[$key]);
        }
    }

    /**
     * 定义析构函数 自动释放获得的锁
     */
    public function __destruct()
    {
        foreach (self::$lockCache as $key => $islock) {
            if ($islock) {
                Model::getInstance()->cache($this->userCache)->getInstance()->delete($key);
                unset(self::$lockCache[$key]);
            }
        }
    }
}