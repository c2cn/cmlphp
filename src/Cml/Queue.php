<?php namespace Cml;
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-02-04 下午20:11
 * @version  2.6
 * cml框架 队列调度中心
 * *********************************************************** */

/**
 * 队列调度中心,封装的队列的操作
 *
 * @package Cml
 */
class Queue
{
    /**
     * 获取Queue
     *
     * @param string | null Queue使用的驱动
     *
     * @return \Cml\Queue\Base
     */
    public static function getQueue($driver = 'Redis')
    {
        static $instance = [];
        if(!isset($instance[$driver])) {
            $instance[$driver] = Cml::getContainer()->make('queue_'.strtolower($driver));
        }
        return $instance[$driver];
    }
}
