<?php namespace Cml\Logger;
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 15-21-22 下午1:11
 * @version  2.5
 * cml框架 Log Redis实现
 * *********************************************************** */

use Cml\Config;
use Cml\Model;

class Redis extends Base
{
    /**
     * 任意等级的日志记录
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        return Model::getInstance()->cache(Config::get('log_use_cache'))->getInstance()->lPush(
            Config::get('log_prefix') . '_' . $level ,
            $this->format($message, $context)
        );
    }
}