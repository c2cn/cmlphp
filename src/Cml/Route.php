<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  2.6
 * cml框架 URL解析类
 * *********************************************************** */
namespace Cml;

use Cml\Http\Request;
use \Cml\Interfaces\Route as RouteInterface;

/**
 * Url解析类,负责路由及Url的解析
 *
 * @package Cml
 */
class Route implements RouteInterface
{
    /**
     * 获取子目录路径。若项目在子目录中的时候为子目录的路径如/subdir/、否则为/
     *
     * @return string
     */
    public function getSubDirName()
    {
        substr(Route::$urlParams['root'], -1) != '/' && Route::$urlParams['root'] .= '/';
        substr(Route::$urlParams['root'], 0, 1) != '/' && Route::$urlParams['root'] = '/' . Route::$urlParams['root'];
        return Route::$urlParams['root'];
    }

    /**
     * 获取应用目录可以是多层目录。如web、admin等
     *
     * @return string
     */
    public function getAppName()
    {
        return trim(Route::$urlParams['path'], '\\/');
    }

    /**
     * 获取控制器名称不带Controller后缀
     *
     * @return string
     */
    public function getControllerName()
    {
        return trim(Route::$urlParams['controller'], '\\/');
    }

    /**
     * 获取控制器名称方法名称
     *
     * @return string
     */
    public function getActionName()
    {
        return trim(Route::$urlParams['action'], '\\/');
    }

    /**
     * 获取不含子目录的完整路径 如: web/Goods/add
     *
     * @return string
     */
    public function getFullPathNotContainSubDir()
    {
        return self::getAppName() . '/' . self::getControllerName() . '/' . self::getActionName();
    }

    /**
     * 是否启用分组
     *
     * @var false
     */
    private static $group = false;

    /**
     * pathinfo数据用来提供给插件做一些其它事情
     *
     * @var array
     */
    private static $pathinfo = [];

    /**
     * 路由类型为GET请求
     *
     * @var int
     */
    const REQUEST_METHOD_GET = 1;

    /**
     * 路由类型为POST请求
     *
     * @var int
     */
    const REQUEST_METHOD_POST = 2;

    /**
     * 路由类型为PUT请求
     *
     * @var int
     */
    const REQUEST_METHOD_PUT = 3;

    /**
     * 路由类型为PATCH请求
     *
     * @var int
     */
    const REQUEST_METHOD_PATCH = 4;

    /**
     * 路由类型为DELETE请求
     *
     * @var int
     */
    const REQUEST_METHOD_DELETE = 5;

    /**
     * 路由类型为OPTIONS请求
     *
     * @var int
     */
    const REQUEST_METHOD_OPTIONS = 6;

    /**
     * 路由类型为任意请求类型
     *
     * @var int
     */
    const REQUEST_METHOD_ANY = 7;

    /**
     * 路由类型 reset 路由
     *
     * @var int
     */
    const RESTROUTE = 8;

    /**
     * 路由规则 [请求方法对应的数字常量]pattern => [/models]/controller/action
     * 'blog/:aid\d' =>'Site/Index/read',
     * 'category/:cid\d/:p\d' =>'Index/index',
     * 'search/:keywords/:p'=>'Index/index',
     * 当路由为RESTROUTE路由时访问的时候会访问路由定义的方法名前加上访问方法如：
     * 定义了一条rest路由 'blog/:aid\d' =>'Site/Index/read' 当请求方法为GET时访问的方法为 Site模块Index控制器下的getRead方法当
     * 请求方法为POST时访问的方法为 Site模块Inde控制器下的postRead方法以此类推.
     *
     * @var array
     */
    private static $rules = [];

    /**
     * 解析得到的请求信息 含应用名、控制器、操作
     *
     * @var array
     */
    public static $urlParams = [
        'path' => '',
        'controller' => '',
        'action' => '',
        'root' => '',
    ];

    /**
     * 解析url
     *
     * @return void
     */
    public function parseUrl()
    {
        $path = '/';
        $urlModel = Config::get('url_model');
        $pathinfo = [];
        $isCli = Request::isCli(); //是否为命令行访问
        if ($isCli) {
            isset($_SERVER['argv'][1]) && $pathinfo = explode('/', $_SERVER['argv'][1]);
        } else {
            if ($urlModel === 1 || $urlModel === 2) { //pathinfo模式(含显示、隐藏index.php两种)SCRIPT_NAME
                if (isset($_GET[Config::get('var_pathinfo')])) {
                    $param = $_GET[Config::get('var_pathinfo')];
                } else {
                    $param = preg_replace('/(.*)\/(.*)\.php(.*)/i', '\\1\\3', $_SERVER['REQUEST_URI']);
                    $scriptName =  preg_replace('/(.*)\/(.*)\.php(.*)/i', '\\1', $_SERVER['SCRIPT_NAME']);

                    if (!empty($scriptName)) {
                        $param = substr($param, strpos($param, $scriptName) + strlen($scriptName));
                    }
                }
                $param = ltrim($param, '/');

                if (!empty($param)) { //无参数时直接跳过取默认操作
                    //获取参数
                    $pathinfo = explode(Config::get('url_pathinfo_depr'), trim(preg_replace(
                        [
                            '/\\'.Config::get('url_html_suffix').'/',
                            '/\&.*/', '/\?.*/'
                        ],
                        '',
                        $param
                    ), Config::get('url_pathinfo_depr')));
                }
            } elseif ($urlModel === 3 && isset($_GET[Config::get('var_pathinfo')])) {//兼容模式
                $urlString = $_GET[Config::get('var_pathinfo')];
                unset($_GET[Config::get('var_pathinfo')]);
                $pathinfo = explode(Config::get('url_pathinfo_depr'), trim(str_replace(
                    Config::get('url_html_suffix'),
                    '',
                    ltrim($urlString, '/')
                ), Config::get('url_pathinfo_depr')));
            }
        }

        isset($pathinfo[0]) && empty($pathinfo[0]) && $pathinfo = [];

        //参数不完整获取默认配置
        if (empty($pathinfo)) {
            $pathinfo = explode('/', trim(Config::get('url_default_action'), '/'));
        }
        self::$pathinfo = $pathinfo;

        //检测路由
        if (self::$rules) {//配置了路由，所有请求通过路由处理
            $isRoute = self::isRoute($pathinfo);
            if ($isRoute[0]) {//匹配路由成功
                $routeArr = explode('/', $isRoute['route']);
                $isRoute = null;
                self::$urlParams['action']= array_pop($routeArr);
                self::$urlParams['controller'] = ucfirst(array_pop($routeArr));
                $controllerPath = '';

               $isOld = Cml::getApplicationDir('app_controller_path');
                while ($dir = array_shift($routeArr)) {
                    if (!$isOld || $path == '/') {
                        $path .= $dir.'/';
                    } else {
                        $controllerPath .= $dir . '/';
                    }
                }
                self::$urlParams['controller'] = $controllerPath . self::$urlParams['controller'];
                unset($routeArr);
            } else {
                self::findAction($pathinfo, $path); //未匹配到路由 按文件名映射查找
            }
        } else {
            self::findAction($pathinfo, $path);//未匹配到路由 按文件名映射查找
        }

        $pathinfo = array_values($pathinfo);
        for ($i = 0; $i < count($pathinfo); $i += 2) {
            $_GET[$pathinfo[$i]] = $pathinfo[$i + 1];
        }

        unset($pathinfo);

        if (self::$urlParams['controller'] == '') {
            //控制器没取到,这时程序会 中止/404，取$path最后1位当做控制器用于异常提醒
            $dir  = explode('/', trim($path, '/'));
            self::$urlParams['controller'] = ucfirst(array_pop($dir));
            $path = empty($dir) ? '' : '/'.implode('/', $dir).'/';
        }
        self::$urlParams['path'] = $path ? $path : '/';
        unset($path);

        //定义URL常量
        $spath = dirname($_SERVER['SCRIPT_NAME']);
        if ($spath == '/' || $spath == '\\') {$spath = '';}
        //定义项目根目录地址
        self::$urlParams['root'] = $spath.'/';
        $_REQUEST = array_merge($_REQUEST, $_GET);
    }

    /**
     * 获取要执行的控制器类名及方法
     *
     */
    public function getControllerAndAction()
    {
        $isOld = Cml::getApplicationDir('app_controller_path');
        //控制器所在路径
        $actionController = (
            $isOld ?
                $isOld . Route::$urlParams['path']
                : Cml::getApplicationDir('apps_path') . Route::$urlParams['path'] . Cml::getApplicationDir('app_controller_path_name') . '/'
            )
            . Route::$urlParams['controller'] . 'Controller.php';

        if (is_file($actionController)) {
            $className = Route::$urlParams['controller'].'Controller';
            $className = ($isOld ? '\Controller' : '')
                .Route::$urlParams['path'].
                ($isOld ? '' : 'Controller'.'/').
                "{$className}";
            $className = str_replace('/', '\\', $className);

            return ['class' => $className, 'action' => Route::$urlParams['action']];
        } else {
            return false;
        }
    }

    /**
     * 从文件查找控制器
     *
     * @param array $pathinfo
     * @param string $path
     */
    private static function findAction(&$pathinfo, &$path)
    {
        $controllerPath = $controllerName = '';
        $controllerAppPath = Cml::getApplicationDir('app_controller_path');//兼容旧版本

        while ($dir = array_shift($pathinfo)) {
            $controllerName = ucfirst($dir);
            if ($controllerAppPath) {
                $controller = $controllerAppPath . $path;
            } else {
                $controller = Cml::getApplicationDir('apps_path') . $path . Cml::getApplicationDir('app_controller_path_name') . '/';
            }
            $controller .= $controllerPath. $controllerName . 'Controller.php';

            if ($path != '/' && is_file($controller) ) {
                self::$urlParams['controller'] = $controllerPath . $controllerName;
                break;
            } else {
                if ($path == '/') {
                    $path .= $dir . '/';
                } else {
                    $controllerPath .= $dir . '/';
                }
            }
        }
        empty(self::$urlParams['controller']) && self::$urlParams['controller'] = $controllerName;//用于404的时候挂载插件用
        self::$urlParams['action'] = array_shift($pathinfo);
    }

    /**
     * 增加get访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public static function get($pattern, $action)
    {
        self::$rules[self::REQUEST_METHOD_GET.self::patternFactory($pattern)] = $action;
    }

    /**
     * 增加post访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public static function post($pattern, $action)
    {
        self::$rules[self::REQUEST_METHOD_POST.self::patternFactory($pattern)] = $action;
    }

    /**
     * 增加put访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public static function put($pattern, $action)
    {
        self::$rules[self::REQUEST_METHOD_PUT.self::patternFactory($pattern)] = $action;
    }

    /**
     * 增加patch访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public static function patch($pattern, $action)
    {
        self::$rules[self::REQUEST_METHOD_PATCH.self::patternFactory($pattern)] = $action;
    }

    /**
     * 增加delete访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public static function delete($pattern, $action)
    {
        self::$rules[self::REQUEST_METHOD_DELETE.self::patternFactory($pattern)] = $action;
    }

    /**
     * 增加options访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public static function options($pattern, $action)
    {
        self::$rules[self::REQUEST_METHOD_OPTIONS.self::patternFactory($pattern)] = $action;
    }

    /**
     * 增加任意访问方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public static function any($pattern, $action)
    {
        self::$rules[self::REQUEST_METHOD_ANY.self::patternFactory($pattern)] = $action;
    }

    /**
     * 增加REST方式路由
     *
     * @param string $pattern 路由规则
     * @param string $action 执行的操作
     *
     * @return void
     */
    public static function rest($pattern, $action)
    {
        self::$rules[self::RESTROUTE.self::patternFactory($pattern)] = $action;
    }

    /**
     * 分组路由
     *
     * @param string $namespace 分组名
     * @param callable $func 闭包
     */
    public static function group($namespace, callable $func)
    {
        if (empty($namespace)) {
            throw new \InvalidArgumentException(Lang::get('_NOT_ALLOW_EMPTY_', '$namespace'));
        }

        self::$group = trim($namespace, '/');

        $func();

        self::$group = false;
    }

    /**
     * 组装路由规则
     *
     * @param $pattern
     *
     * @return string
     */
    private static function patternFactory($pattern)
    {
        if (self::$group) {
            return self::$group . '/' . ltrim($pattern);
        } else {
            return $pattern;
        }
    }


    /**
     * 匹配路由
     *
     * @param string $pathinfo
     *
     * @return mixed
     */
    private static function isRoute(&$pathinfo)
    {
        empty($pathinfo) && $pathinfo[0] = '/';//网站根地址
        $issuccess = [];
        $route = self::$rules;
        isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] = self::REQUEST_METHOD_ANY;
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $rmethod = self::REQUEST_METHOD_GET;
                break;
            case 'POST':
                $rmethod = self::REQUEST_METHOD_POST;
                break;
            case 'PUT':
                $rmethod = self::REQUEST_METHOD_PUT;
                break;
            case 'PATCH':
                $rmethod = self::REQUEST_METHOD_PATCH;
                break;
            case 'DELETE':
                $rmethod = self::REQUEST_METHOD_DELETE;
                break;
            case 'OPTIONS':
                $rmethod = self::REQUEST_METHOD_OPTIONS;
                break;
            default :
                $rmethod = self::REQUEST_METHOD_ANY;
        }

        foreach ($route as $k => $v) {
            $rulesmethod = substr($k, 0, 1);
            if ($rulesmethod != $rmethod &&
                $rulesmethod != self::REQUEST_METHOD_ANY &&
                $rulesmethod != self::RESTROUTE) { //此条路由不符合当前请求方式
                continue;
            }
            unset($v);
            $singleRule = substr($k, 1);
            $arr = $singleRule === '/' ? [$singleRule] : explode('/', ltrim($singleRule, '/'));

            if ($arr[0] == $pathinfo[0]) {
                array_shift($arr);
                foreach ($arr as $key => $val) {
                    if (isset($pathinfo[$key + 1]) && $pathinfo[$key + 1] !== '') {
                        if (strpos($val, '\d') && !is_numeric($pathinfo[$key + 1])) {//数字变量
                            $route[$k] = false;//匹配失败
                            break 1;
                        } elseif (strpos($val, ':') === false && $val != $pathinfo[$key + 1]){//字符串
                            $route[$k] = false;//匹配失败
                            break 1;
                        }
                    } else {
                        $route[$k] = false;//匹配失败
                        break 1;
                    }
                }
            } else {
                $route[$k] = false;//匹配失败
            }

            if ($route[$k] !== false) {//匹配成功的路由
                $issuccess[] = $k;
            }
        }

        if (empty($issuccess)) {
            $returnArr[0] = false;
        } else {
            //匹配到多条路由时 选择最长的一条（匹配更精确）
            usort($issuccess, function($item1, $item2) {
                return strlen($item1) >= strlen($item2) ? 0 : 1;
            });

            if (is_callable($route[$issuccess[0]])) {
                call_user_func($route[$issuccess[0]]);
                Cml::cmlStop();
            }

            $route[$issuccess[0]] = trim($route[$issuccess[0]], '/');

            //判断路由的正确性
            if (count(explode('/', $route[$issuccess[0]])) < 2) {
                throw new \InvalidArgumentException(Lang::get('_ROUTE_PARAM_ERROR_',  substr($issuccess[0], 1)));
            }

            $returnArr[0] = true;
            $successRoute = explode('/', $issuccess[0]);
            foreach ($successRoute as $key => $val) {
                $t = explode('\d', $val);
                if (strpos($t[0], ':') !== false) {
                    $_GET[ltrim($t[0], ':')] = $pathinfo[$key];
                }
                unset($pathinfo[$key]);
            }

            if (substr($issuccess[0], 0 , 1) == self::RESTROUTE) {
                $actions = explode('/', $route[$issuccess[0]]);
                $arrKey = count($actions)-1;
                $actions[$arrKey] = strtolower($_SERVER['REQUEST_METHOD']) . ucfirst($actions[$arrKey]);
                $route[$issuccess[0]] = implode('/', $actions);
            }

            $returnArr['route'] = $route[$issuccess[0]];
        }
        return $returnArr;
    }

    /**
     * 获取解析后的pathinfo信息
     *
     * @return array
     */
    public static function getPathInfo()
    {
        return self::$pathinfo;
    }

    /**
     * 载入应用单独的路由
     *
     * @param string $app 应用名称
     */
    public static function loadAppRoute($app = 'web')
    {
        static $loaded = [];
        if (isset($loaded[$app]) ) {
            return;
        }
        $appRoute = Cml::getApplicationDir('apps_path').DIRECTORY_SEPARATOR.$app.DIRECTORY_SEPARATOR.Cml::getApplicationDir('app_config_path_name').DIRECTORY_SEPARATOR.'route.php';
        if (!is_file($appRoute)) {
            throw new \InvalidArgumentException(Lang::get('_NOT_FOUND_', $app.DIRECTORY_SEPARATOR.Cml::getApplicationDir('app_config_path_name').DIRECTORY_SEPARATOR.'route.php'));
        }

        $loaded[$app] = 1;
        Cml::requireFile($appRoute);
    }
}