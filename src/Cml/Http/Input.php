<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  2.5
 * cml框架 输入类管理类
 * *********************************************************** */
namespace Cml\Http;

class Input
{

    /**
     * 获取get string数据
     *
     * @param string $name 要获取的变量
     *
     * @return string|null
     */
    public static function getString($name, $default = null)
    {
        if (isset($_GET[$name]) && $_GET[$name] !== '' ) return trim(htmlspecialchars($_GET[$name], ENT_QUOTES, 'UTF-8'));
        return $default;
    }

    /**
     * 获取post string数据
     *
     * @param string $name 要获取的变量
     *
     * @return string|null
     */
    public static function postString($name, $default = null)
    {
        if (isset($_POST[$name]) && $_POST[$name] !== '' ) return trim(htmlspecialchars($_POST[$name], ENT_QUOTES, 'UTF-8'));
        return $default;
    }

    /**
     * 获取$_REQUEST string数据
     *
     * @param string $name 要获取的变量
     *
     * @return null|string
     */
    public static function requestString($name, $default = null)
    {
        if (isset($_REQUEST[$name]) && $_REQUEST[$name] !== '' ) return trim(htmlspecialchars($_REQUEST[$name], ENT_QUOTES, 'UTF-8'));
        return $default;
    }

    /**
     * 获取get int数据
     *
     * @param string $name
     *
     * @return int|null
     */
    public static function getInt($name, $default = null)
    {
        if (isset($_GET[$name]) && $_GET[$name] !== '' ) return intval($_GET[$name]);
        return (is_null($default) ? null : intval($default));
    }

    /**
     * 获取post int数据
     *
     * @param string $name
     *
     * @return int|null
     */
    public static function postInt($name, $default = null)
    {
        if (isset($_POST[$name]) && $_POST[$name] !== '' ) return intval($_POST[$name]);
        return (is_null($default) ? null : intval($default));
    }

    /**
     * 获取$_REQUEST int数据
     *
     * @param string $name 要获取的变量
     *
     * @return null|int
     */
    public static function requestInt($name, $default = null)
    {
        if (isset($_REQUEST[$name]) && $_REQUEST[$name] !== '' ) return intval($_REQUEST[$name]);
        return (is_null($default) ? null : intval($default));
    }

    /**
     * 获取get bool数据
     *
     * @param string $name
     *
     * @return bool|null
     */
    public static function getBool($name, $default = null)
    {
        if (isset($_GET[$name]) && $_GET[$name] !== '' ) return ((bool)$_GET[$name]);
        return (is_null($default) ? null : ((bool)$default));
    }

    /**
     * 获取post bool数据
     *
     * @param string $name
     *
     * @return bool|null
     */
    public static function postBool($name, $default = null)
    {
        if (isset($_POST[$name]) && $_POST[$name] !== '' ) return ((bool)$_POST[$name]);
        return (is_null($default) ? null : ((bool)$default));
    }

    /**
     * 获取$_REQUEST bool数据
     *
     * @param string $name 要获取的变量
     *
     * @return null|bool
     */
    public static function requestBool($name, $default = null)
    {
        if (isset($_REQUEST[$name]) && $_REQUEST[$name] !== '' ) return ((bool)$_REQUEST[$name]);
        return (is_null($default) ? null : ((bool)$default));
    }
}