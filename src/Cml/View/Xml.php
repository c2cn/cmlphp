<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  2.5
 * cml框架 视图 Xml渲染引擎
 * *********************************************************** */
namespace Cml\View;

use Cml\Config;
use Cml\Plugin;

class Xml extends Base {

    /**
     * 输出数据
     *
     */
    public function display() {
        header('Content-Type: application/xml;charset='.Config::get('default_charset'));
        Plugin::hook('cml.before_cml_stop');
        exit($this->array2xml($this->args));
    }

    /**
     * 数组转xml
     *
     * @param array $arr
     * @param int $level
     *
     * @return string
     */
    private  function array2xml($arr, $level = 1) {
        $str = ($level == 1) ? "<?xml version=\"1.0\" encoding=\"".Config::get('default_charset')."\"?>\r\n<root>\r\n" : '';
        $space = str_repeat("\t", $level);
        foreach ($arr as $key => $val) {
            if (is_numeric($key)) {
                $key = 'item';
            }
            if (!is_array($val)) {
                if (is_string($val) && preg_match('/[&<>"\'\?]+/', $val)) {
                    $str .= $space."<$key><![CDATA[".$val.']]>'."</$key>\r\n";
                } else {
                    $str .= $space."<$key>".$val."</$key>\r\n";
                }
            } else {
                $str .= $space."<$key>\r\n".self::array2xml($val, $level+1).$space."</$key>\r\n";
            }
        }
        if ($level == 1) {
            $str .= '</root>';
        }
        return $str;
    }

}