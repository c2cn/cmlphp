<?php namespace Cml\Tools;
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 2015/11/9 16:01
 * @version  2.5
 * cml框架 系统cli命令解析
 * *********************************************************** */
use Cml\Http\Request;

class RunCliCommand
{
    /**
     * 判断从命令行执行的系统命令
     *
     */
    public static function runCliCommand()
    {
        Request::isCli() || exit('please run on cli!');

        if($_SERVER['argv'][1] != 'cml.cmd') {
            return ;
        }
        $deper = (Request::isCli() ? PHP_EOL : '<br />');

        echo $deper.$deper."//^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^{$deper}";
        echo "//^^^^^^^^^^^^^^^^^^^^^^^^^   cml.cmd start!   ^^^^^^^^^^^^^^^^^^^{$deper}";

        array_shift($_SERVER['argv']);
        array_shift($_SERVER['argv']);
        $tool = array_shift($_SERVER['argv']);
        $args = array();
        foreach($_SERVER['argv'] as $val) {
            $args[] = $val;
        }

        call_user_func_array('\\Cml\\Tools\\'.$tool, $args);

        echo $deper.$deper."//^^^^^^^^^^^^^^^^^^^^^^^^^ cml.cmd end! ^^^^^^^^^^^^^^^^^^^^^^^^^{$deper}";
        echo "//^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^{$deper}";
        exit();
    }
}