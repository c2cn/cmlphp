<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @desc 数据库保存session
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 13-8-9 下午2:22
 * *********************************************************** */
/*
CREATE TABLE `cml_session` (
    `id` char(32) NOT NULL,
    `value` varchar(5000) NOT NULL,
    `time` int(11) unsigned NOT NULL,
    PRIMARY KEY(`id`)
)ENGINE=MEMORY DEFAULT CHARSET=utf8;
*/
namespace Cml;

class Session
{
    private $lifeTime; //session超时时间

    /**
     * @var \Cml\Db\Mysql\Pdo || Cml\Cache\File
     *
     */
    private $handler;

    public static function init()
    {
        $cmlSession = new Session();
        $cmlSession->lifeTime = ini_get('session.gc_maxlifetime');
        if (Config::get('session_user_LOC') == 'db') {
            $cmlSession->handler = Model::getInstance()->db();
        } else {
            $cmlSession->handler = Model::getInstance()->cache() ;
        }
        ini_set('session.save_handler', 'user');
        session_module_name('user');
        session_set_save_handler(
            array($cmlSession, 'open'), //运行session_start()时执行
            array($cmlSession, 'close'), //在脚本执行结束或调用session_write_close(),或session_destroy()时被执行，即在所有session操作完后被执行
            array($cmlSession, 'read'), //在执行session_start()时执行，因为在session_start时会去read当前session数据
            array($cmlSession, 'write'), //此方法在脚本结束和session_write_close强制提交SESSION数据时执行
            array($cmlSession, 'destroy'), //在执行session_destroy()时执行
            array($cmlSession, 'gc') //执行概率由session.gc_probability和session.gc_divisor的值决定，时机是在open,read之后，session_start会相继执行open,read和gc
        );
        ini_get('session.auto_start') || session_start(); //自动开启session,必须在session_set_save_handler后面执行
    }

    public function open($savePath, $sessionName)
    {
        return true;
    }

    public function close()
    {
        if (Config::get('session_user_LOC') == 'db') {
            $this->handler ->wlink = null;
        }
        //$GLOBALS['debug'] && \Cml\Debug::stop(); 开启ob_start()的时候 php此时已经不能使用压缩，所以这边输出的数据是没压缩的，而之前已经告诉浏览器数据是压缩的，所以会导致火狐、ie不能正常解压
        //$this->gc($this->lifeTime);
        return true;
    }

    public  function read($sessionId)
    {
        $result = $this->handler ->get('session-id-'.$sessionId);
        if (Config::get('session_user_LOC') == 'db') {
            return $result ? $result[0]['value'] : null;
        } else {
            return $result ? $result : null;
        }
    }

    public function write($sessionId, $value)
    {
        if (Config::get('session_user_LOC') == 'db') {
            $this->handler ->set('session', array(
                'id' => $sessionId,
                'value' => $value,
                'time' => Cml::$nowTime
            ));
        } else {
            $this->handler->set('session-id-'.$sessionId, $value, $this->lifeTime);
        }
        return true;
    }

    public function destroy($sessionId)
    {
        $this->handler->delete('session-id-'.$sessionId);
        return true;
    }

    public function gc($lifeTime = 0)
    {
        if (Config::get('session_user_LOC') == 'db') {
            $lifeTime || $lifeTime = $this->lifeTime;
            $stmt = $this->handler->prepare('DELETE FROM `cml_session` where `time` < '.Cml::$nowTime - $lifeTime);
            $stmt->execute();
        } else {
            //cache 本身会回收
        }
        return true;
    }
}