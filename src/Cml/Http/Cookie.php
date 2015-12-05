<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  2.5
 * cml框架 Cookie管理类
 * *********************************************************** */
namespace Cml\Http;

use Cml\Cml;
use Cml\Config;
use Cml\Encry;

class Cookie
{
    /**
     * 判断Cookie是否存在
     *
     * @param $key string 要判断Cookie
     *
     * @return bool
     */
    public static function isExist($key)
    {
        return isset($_COOKIE[Config::get('cookie_prefix').$key]);
    }

    /**
     * 获取某个Cookie值
     *
     * @param string $name
     *
     * @return bool|mixed
     */
    public static function get($name)
    {
        if (!self::isExist($name)) return false;
        $value   = $_COOKIE[Config::get('cookie_prefix').$name];
        return Encry::decrypt($value);
    }

    /**
     * 设置某个Cookie值
     *
     * @param $name
     * @param $value
     * @param string $expire
     * @param string $path
     * @param string $domain
     *
     * @return void
     */
    public static function set($name, $value, $expire = '',$path = '', $domain = '')
    {
        empty($expire) && $expire = Config::get('cookie_expire');
        empty($path) && $path = Config::get('cookie_path');
        empty($domain) && $domain = Config::get('cookie_domain');

        $expire = !empty($expire) ? Cml::$nowTime + $expire : 0;
        $value = Encry::encrypt($value);
        setcookie(Config::get('cookie_prefix').$name, $value, $expire, $path, $domain);
        $_COOKIE[Config::get('cookie_prefix').$name] = $value;
    }

    /**
     * 删除某个Cookie值
     *
     * @param $name
     *
     * @return void
     */
    public static function delete($name)
    {
        self::set($name, '', Cml::$nowTime - 3600);
        unset($_COOKIE[Config::get('cookie_prefix').$name]);
    }

    /**
     * 清空Cookie值
     *
     * @return void
     */
    public static function clear()
    {
        unset($_COOKIE);
    }
}