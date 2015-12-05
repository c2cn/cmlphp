<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  2.5
 * cml框架 文件Log类
 * *********************************************************** */
namespace Cml;

class Log
{

    /**
     * 获取文件名
     *
     * @param string $name
     *
     * @return string
     */
    private static function getDir($name)
    {
        $name = str_replace(array("..", "/", "\\"), "", $name);
        $dir = CML_RUNTIME_LOGS_PATH.DIRECTORY_SEPARATOR.'AppLogs'.date('/Y/m/d');
        is_dir($dir) || mkdir($dir, 0700, true);
        return $dir.DIRECTORY_SEPARATOR.$name;
    }

    /**
     * 写入缓存
     *
     * @param string $key key
     * @param mixed $value 要缓存的数据
     *
     * @return bool
     */
    public static function save($name, $value)
    {
        is_array($value) || (array)$value;
        $value['logtime'] = date('Y-m-d H:i:s');
        file_put_contents(
            self::getDir($name),
            json_encode($value, PHP_VERSION >= '5.4.0' ? JSON_UNESCAPED_UNICODE : 0),
            LOCK_EX
        );
        return true;
    }

    /**
     * 删除缓存
     *
     * @param string $key key
     *
     * @return bool
     */
    public static function rm($name)
    {
        unlink(self::getDir($name));
        return true;
    }

}