<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 MySql数据库 swoole协程驱动类
 * *********************************************************** */

namespace Cml\Db\MySql;

use Cml\Cml;
use Cml\Debug;
use Cml\Exception\PdoConnectException;
use Cml\Log;
use Cml\Plugin;
use \Swoole\Coroutine\MySQL;
use Swoole\Coroutine\Mysql\Statement;

class Swoole extends Pdo
{
    /**
     * Db连接
     *
     * @param string $host 数据库host
     * @param string $username 数据库用户名
     * @param string $password 数据库密码
     * @param string $dbName 数据库名
     * @param string $charset 字符集
     * @param string $engine 引擎
     * @param bool $pConnect 是否为长连接
     *
     * @return mixed
     */
    public function connect($host, $username, $password, $dbName, $charset = 'utf8', $engine = '', $pConnect = false)
    {
        $host = explode(':', $host);

        $doConnect = function () use ($host, $pConnect, $charset, $username, $password, $dbName) {
            $swooleMysql = new MySQL();
            if (!$swooleMysql->connect([
                'host' => $host[0],
                'port' => isset($host[1]) ? $host[1] : 3306,
                'user' => $username,
                'password' => $password,
                'database' => $dbName,
                'charset' => $charset,
                'fetch_mode' => true
            ])) {
                throw new PdoConnectException(
                    'Pdo Connect Error! ｛' .
                    $host[0] . (isset($host[1]) ? ':' . $host[1] : '') . ', ' . $dbName .
                    '} Code:' . $swooleMysql->connect_errno . ', ErrorInfo!:' . $swooleMysql->connect_error,
                    0
                );
            }
            return $swooleMysql;
        };
        $link = $doConnect();

        //$link->exec("SET names $charset");
        isset($this->conf['sql_mode']) && $link->query('set sql_mode="' . $this->conf['sql_mode'] . '";'); //放数据库配 特殊情况才开
        if (!empty($engine) && $engine == 'InnoDB') {
            $link->query('SET innodb_flush_log_at_trx_commit=2');
        }
        return $link;
    }

    /**
     * 预处理语句
     *
     * @param string $sql 要预处理的sql语句
     * @param \Swoole\Coroutine\MySQL $link
     * @param bool $resetParams
     *
     * @return Statement
     */

    public function prepare($sql, $link = null, $resetParams = true)
    {
        $resetParams && $this->reset();
        is_null($link) && $link = $this->currentQueryIsMaster ? $this->wlink : $this->rlink;

        $this->currentSql = $sql;
        $this->currentPrepareIsResetParams = $resetParams;

        $stmt = $link->prepare($sql);//pdo默认情况prepare出错不抛出异常只返回Pdo::errorInfo
        if ($stmt === false) {
            if (in_array($link->errno, [2006, 2013])) {
                $link = $this->connectDb($this->currentQueryIsMaster ? 'wlink' : 'rlink', true);
                $stmt = $link->prepare($sql);
                if ($stmt !== false) {
                    return $stmt;
                }
            }
            throw new \InvalidArgumentException(
                'Pdo Prepare Sql error! ,【Sql: ' . $this->buildDebugSql() . '】,【Code: ' . $link->errno . '】, 【ErrorInfo!: 
                ' . $link->error . '】 '
            );
        }
        return $stmt;
    }

    /**
     * 执行预处理语句
     *
     * @param object $stmt Statement
     * @param bool $clearBindParams
     *
     * @return bool
     */
    public function execute($stmt, $clearBindParams = true)
    {
        //empty($param) && $param = $this->bindParams;
        $this->conf['log_slow_sql'] && $startQueryTimeStamp = microtime(true);

        $error = false;
        if (!$stmt->execute($this->bindParams)) {
            $link = $this->currentQueryIsMaster ? $this->wlink : $this->rlink;
            $error = $link->error;
            if (in_array($link->errno, [2006, 2013])) {
                $link = $this->connectDb($this->currentQueryIsMaster ? 'wlink' : 'rlink', true);
                $stmt = $this->prepare($this->currentSql, $link, $this->currentPrepareIsResetParams);

                if (!$stmt->execute($this->bindParams)) {
                    $error = $link->error;
                } else {
                    $error = false;
                }
            }
        }

        if ($error) {
            throw new \InvalidArgumentException('Pdo execute Sql error!,【Sql : ' . $this->buildDebugSql() . '】,【Code: ' . $link->errno . '】,【Error:' . $error . '】');
        }

        $slow = 0;
        if ($this->conf['log_slow_sql']) {
            $queryTime = microtime(true) - $startQueryTimeStamp;
            if ($queryTime > $this->conf['log_slow_sql']) {
                if (Plugin::hook('cml.mysql_query_slow', ['sql' => $this->buildDebugSql(), 'query_time' => $queryTime]) !== false) {
                    Log::notice('slow_sql', ['sql' => $this->buildDebugSql(), 'query_time' => $queryTime]);
                }
                $slow = $queryTime;
            }
        }

        if (Cml::$debug) {
            $this->debugLogSql($slow > 0 ? Debug::SQL_TYPE_SLOW : Debug::SQL_TYPE_NORMAL, $slow);
        }

        $this->currentQueryIsMaster = true;
        $this->currentSql = '';
        $clearBindParams && $this->clearBindParams();
        return true;
    }
}

