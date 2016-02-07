<?php namespace Cml\Queue;
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-02-04 下午20:11
 * @version  2.5
 * cml框架 队列Redis驱动
 * *********************************************************** */
use Cml\Config;
use Cml\Model;

class Redis extends Base
{

    /**
     * 从列表头入队
     *
     * @param string $name
     * @param mixed $data
     *
     * @return mixed
     */
    public function lPush($name, $data)
    {
        return $this->getDriver()->lPush($name, $this->encodeDate($data));
    }

    /**
     * 从列表头出队
     *
     * @param string $name
     * @param mixed $data
     *
     * @return mixed
     */
    public function lPop($name, $data)
    {
        $data = $this->getDriver()->lPop($name);
        $data && $this->decodeDate($data);
        return $data;
    }

    /**
     * 从列表尾入队
     *
     * @param string $name
     * @param mixed $data
     *
     * @return mixed
     */
    public function rPush($name, $data)
    {
        return $this->getDriver()->rPush($name, $this->encodeDate($data));
    }

    /**
     * 从列表尾出队
     *
     * @param string $name
     * @param mixed $data
     *
     * @return mixed
     */
    public function rPop($name, $data)
    {
        $data = $this->getDriver()->rPop($name);
        $data && $this->decodeDate($data);
        return $data;
    }

    /**
     * 从列表尾出队
     *
     * @param string $from
     * @param string $to
     *
     * @return mixed
     */
    public function rPopLpush($from, $to)
    {
        return $this->getDriver()->rpoplpush($from, $to);
    }

    /**
     * 返回驱动
     *
     * @return \Redis
     */
    private function getDriver()
    {
        return Model::getInstance()->cache(Config::get('queue_use_cache'))->getInstance();
    }
}