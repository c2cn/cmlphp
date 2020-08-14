<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-13 上午11:01
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 配置处理类
 * *********************************************************** */

namespace Cml;

use Cml\Exception\ConfigNotFoundException;

/**
 * 配置读写类、负责配置文件的读取
 *
 * @package Cml
 */
class Config
{
    /**
     * 配置文件类型
     *
     * @var string
     */
    public static $isLocal = 'product';

    /**
     * 存放了所有配置信息
     *
     * @var array
     */
    private static $_content = [
        'normal' => []
    ];

    public static function init()
    {
        self::$isLocal = Cml::getContainer()->make('cml_environment')->getEnv();

        //引入框架惯例配置文件
        $cmlConfig = Cml::requireFile(CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php');

        //应用正式配置文件
        $appConfig = Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . self::$isLocal . DIRECTORY_SEPARATOR . 'normal.php';

        is_file($appConfig) ? $appConfig = Cml::requireFile($appConfig)
            : exit('Config File [' . Config::$isLocal . '/normal.php] Not Found Please Check！');
        is_array($appConfig) || $appConfig = [];

        $commonConfig = Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . 'common.php';
        $commonConfig = is_file($commonConfig) ? Cml::requireFile($commonConfig) : [];

        Config::set(array_merge($cmlConfig, $commonConfig, $appConfig));//合并配置

        if (Config::get('debug')) {
            Cml::$debug = true;
            $GLOBALS['debug'] = true;//开启debug
            Debug::addTipInfo(CML_CORE_PATH . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php', Debug::TIP_INFO_TYPE_INCLUDE_FILE);
            Debug::addTipInfo(Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . Config::$isLocal . DIRECTORY_SEPARATOR . 'normal.php', Debug::TIP_INFO_TYPE_INCLUDE_FILE);
            empty($commonConfig) || Debug::addTipInfo(Cml::getApplicationDir('global_config_path') . DIRECTORY_SEPARATOR . 'common.php', Debug::TIP_INFO_TYPE_INCLUDE_FILE);
        }
    }

    /**
     * 获取配置参数不区分大小写
     *
     * @param string $key 支持.获取多维数组
     * @param string $default 不存在的时候默认值
     *
     * @return mixed
     */
    public static function get($key = null, $default = null)
    {
        // 无参数时获取所有
        if (empty($key)) {
            return self::$_content;
        }

        $key = strtolower($key);
        return Cml::doteToArr($key, self::$_content['normal'], $default);
    }

    /**
     * 设置配置【语言】 支持批量设置 /a.b.c方式设置
     *
     * @param string|array $key 要设置的key,为数组时是批量设置
     * @param mixed $value 要设置的值
     *
     * @return null
     */
    public static function set($key, $value = null)
    {
        if (is_array($key)) {
            static::$_content['normal'] = array_merge(static::$_content['normal'], array_change_key_case($key));
        } else {
            $key = strtolower($key);

            if (!strpos($key, '.')) {
                static::$_content['normal'][$key] = $value;
                return null;
            }

            // 多维数组设置 A.B.C = 1
            $key = explode('.', $key);
            $tmp = null;
            foreach ($key as $k) {
                if (is_null($tmp)) {
                    if (isset(static::$_content['normal'][$k]) === false) {
                        static::$_content['normal'][$k] = [];
                    }
                    $tmp = &static::$_content['normal'][$k];
                } else {
                    is_array($tmp) || $tmp = [];
                    isset($tmp[$k]) || $tmp[$k] = [];
                    $tmp = &$tmp[$k];
                }
            }
            $tmp = $value;
            unset($tmp);
        }
        return null;
    }

    /**
     * 从文件载入Config
     *
     * @param string $file
     * @param bool $global 是否从全局加载,true为从全局加载、false为载入当前app下的配置、字符串为从指定的app下加载
     *
     * @return array
     */
    public static function load($file, $global = true)
    {
        if (isset(static::$_content[$global . $file])) {
            return static::$_content[$global . $file];
        } else {
            $filePath =
                (
                $global === true
                    ? Cml::getApplicationDir('global_config_path')
                    : Cml::getApplicationDir('apps_path')
                    . '/' . ($global === false ? Cml::getContainer()->make('cml_route')->getAppName() : $global) . '/'
                    . Cml::getApplicationDir('app_config_path_name')
                )
                . '/' . ($global === true ? self::$isLocal . DIRECTORY_SEPARATOR : '') . $file . '.php';

            if (!is_file($filePath)) {
                throw new ConfigNotFoundException(Lang::get('_NOT_FOUND_', $filePath));
            }
            static::$_content[$global . $file] = Cml::requireFile($filePath);
            return static::$_content[$global . $file];
        }
    }
}
