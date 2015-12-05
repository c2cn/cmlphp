<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  2.5
 * cml框架 视图 Json渲染引擎
 * *********************************************************** */
namespace Cml\View;

use Cml\Config;
use Cml\Debug;
use Cml\Plugin;

class Json extends Base {

    /**
     * 输出数据
     *
     */
    public function display() {
        header('Content-Type: application/json;charset='.Config::get('default_charset'));
        if ($GLOBALS['debug']) {
            $sqls = Debug::getSqls();
            if (isset($sqls[0])) {
                $this->args['sql'] = implode($sqls, ', ');
            }
        }
        Plugin::hook('cml.before_cml_stop');
        exit(json_encode($this->args, PHP_VERSION >= '5.4.0' ? JSON_UNESCAPED_UNICODE : 0));
    }

}