<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 16-9-6 下午3:07
 * @version  2.6
 * cml框架 路由接口。使用第三方路由必须封装实现本接口
 * *********************************************************** */

namespace Cml\Interfaces;

/**
 * Interface Route
 *
 * @package Cml\Interfaces
 */
interface Route
{
    /**
     * 获取子目录路径。若项目在子目录中的时候为子目录的路径如/subdir/、否则为/
     *
     * @return string
     */
    public function getSubDirName();

    /**
     * 获取应用目录可以是多层目录。如web、admin等
     *
     * @return string
     */
    public function getAppName();

    /**
     * 获取控制器名称不带Controller后缀
     *
     * @return string
     */
    public function getControllerName();

    /**
     * 获取控制器名称方法名称
     *
     * @return string
     */
    public function getActionName();

    /**
     * 获取不含子目录的完整路径 如: web/Goods/add
     *
     * @return string
     */
    public function getFullPathNotContainSubDir();

    /**
     * 解析url参数
     * 框架在完成必要的启动步骤后。会调用 Cml::getContainer()->make('cml_route')->parseUrl();进行路由地址解析供上述几个方法调用。
     *
     * @return mixed
     */
    public function parseUrl();

    /**
     * 返回要执行的控制器及方法。必须返回一个包含 controller和action键的数组
     * 如:['controller' => '/var/wwwroot/xxxxx/adminbase/Controller/IndexController.php', 'action' => 'index']
     * 在parseUrl之后框架会根据解析得到的参数去自动载入相关的配置文件然后调用Cml::getContainer()->make('cml_route')->getControllerAndAction();执行相应的方法
     *
     * @return mixed
     */
    public function getControllerAndAction();

}
