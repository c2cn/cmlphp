<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  2.5
 * cml框架 MySql数据库 Pdo驱动类
 * *********************************************************** */
namespace Cml\Db\MySql;

use Cml\Config;
use Cml\Db\Base;
use Cml\Debug;
use Cml\Lang;
use Cml\Model;

/**
 * Orm MySql数据库Pdo实现类
 *
 * @package Cml\Db\MySql
 */
class Pdo extends Base
{
    /**
     * 数据库连接串
     *
     * @param $conf
     */
    public function __construct($conf)
    {
        $this->conf = $conf;
        $this->tablePrefix = $this->conf['master']['tableprefix'];
    }

    /**
     * 获取当前db所有表名
     *
     * @return array
     */
    public function getTables()
    {
        $stmt = $this->prepare('SHOW TABLES;', $this->rlink);
        $this->execute($stmt);

        $tables = array();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tables[] = $row['Tables_in_'.$this->conf['master']['dbname']];
        }
        return $tables;
    }

    /**
     * 获取表字段
     *
     * @param string $table 表名
     * @param mixed $tablePrefix 表前缀 为null时代表table已经带了前缀
     * @param int $filter 0 获取表字段详细信息数组 1获取字段以,号相隔组成的字符串
     *
     * @return mixed
     */
    public function getDbFields($table, $tablePrefix = null, $filter = 0)
    {
        static $dbFieldCache = array();

        if ($filter == 1 && $GLOBALS['debug']) return '*'; //debug模式时直接返回*
        $table = is_null($tablePrefix) ? strtolower($table) : $tablePrefix.strtolower($table);

        if (isset($dbFieldCache[$table])) {
            $info = $dbFieldCache[$table];
        } else {
            $info = \Cml\simpleFileCache($this->conf['master']['dbname'].'.'.$table);
            if (!$info || $GLOBALS['debug']) {
                $stmt = $this->prepare("SHOW COLUMNS FROM $table", $this->rlink, false);
                $this->execute($stmt, false);
                $info = array();
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $info[$row['Field']] = array(
                        'name'    => $row['Field'],
                        'type'    => $row['Type'],
                        'notnull' => (bool) ($row['Null'] === ''), // not null is empty, null is yes
                        'default' => $row['Default'],
                        'primary' => (strtolower($row['Key']) == 'pri'),
                        'autoinc' => (strtolower($row['Extra']) == 'auto_increment'),
                    );
                }

                count($info) > 0 && \Cml\simpleFileCache($this->conf['master']['dbname'].'.'.$table, $info);
            }
            $dbFieldCache[$table] = $info;
        }

        if ($filter) {
            if (count($info) > 0) {
                $info = implode('`,`', array_keys($info));
                $info = '`'.$info.'`';
            } else {
                return '*';
            }
        }
        return $info;
    }

    /**
     * 根据key取出数据
     *
     * @param string $key get('user-uid-123');
     * @param bool $and 多个条件之间是否为and  true为and false为or
     * @return array array('uid'=>123, 'username'=>'abc')
     *
     * @return array
     */
    public function get($key, $and = true)
    {
        list($tableName, $condition) = $this->parseKey($key, $and);
        $tableName = $this->tablePrefix.$tableName;
        $fields = Config::get('db_fields_cache') ? $this->getDbFields($tableName, null, 1) : '*';

        $sql = "SELECT {$fields} FROM {$tableName} WHERE {$condition} LIMIT 0, 1000";
        $cacheKey = md5($sql.json_encode($this->bindParams)).$this->getCacheVer($tableName);

        $return = Model::getInstance()->cache()->get($cacheKey);
        if ($return === false) { //cache中不存在这条记录
            $stmt = $this->prepare($sql, $this->rlink);
            $this->execute($stmt);
            $return = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            Model::getInstance()->cache()->set($cacheKey, $return, $this->conf['cache_expire']);
        } else {
            $this->bindParams = array();
        }

        return $return;
    }

    /**
     * 根据key 新增 一条数据
     *
     * @param string $table
     * @param array $data eg: array('username'=>'admin', 'email'=>'linhechengbush@live.com')
     *
     * @return bool|int
     */
    public function set($table, $data)
    {
        $tableName = $this->tablePrefix.$table;
        if (is_array($data)) {
            $s = $this->arrToCondition($data, $table, $this->tablePrefix);
            $stmt = $this->prepare("INSERT INTO {$tableName} SET {$s}", $this->wlink);
            $this->execute($stmt);

            $this->setCacheVer($tableName);
            return $this->insertId();
        } else {
            return false;
        }
    }

    /**
     * 根据key更新一条数据
     *
     * @param string $key eg 'user-uid-$uid' 如果条件是通用whereXX()、表名是通过table()设定。这边可以直接传$data的数组
     * @param array | null $data eg: array('username'=>'admin', 'email'=>'linhechengbush@live.com')
     * @param bool $and 多个条件之间是否为and  true为and false为or
     *
     * @return boolean
     */
    public function update($key, $data = null, $and = true)
    {
        $tablePrefix = $this->tablePrefix;
        $tableName = $condition = '';

        if (is_array($data)) {
            list($tableName, $condition) = $this->parseKey($key, $and, true, true);
        } else {
            $data = $key;
        }

        $tableName = empty($tableName) ? $this->getRealTableName(key($this->table)) : $tablePrefix.$tableName;
        empty($tableName) && \Cml\throwException(Lang::get('_PARSE_SQL_ERROR_NO_TABLE_', 'update'));
        $s = $this->arrToCondition($data, substr($tableName, strlen($tablePrefix)), $tablePrefix);
        $whereCondition = $this->sql['where'];
        $whereCondition .= empty($condition) ?  '' : (empty($whereCondition) ? 'WHERE ' : '').$condition;
        empty($whereCondition) && \Cml\throwException(Lang::get('_PARSE_SQL_ERROR_NO_CONDITION_', 'update'));
        $stmt = $this->prepare("UPDATE {$tableName} SET {$s} {$whereCondition}", $this->wlink);
        $this->execute($stmt);

        $this->setCacheVer($tableName);
        return $stmt->rowCount();
    }

    /**
     * 根据key值删除数据
     *
     * @param string $key eg: 'user-uid-$uid'
     * @param bool $and 多个条件之间是否为and  true为and false为or
     *
     * @return boolean
     */
    public function delete($key = '', $and = true)
    {
        $tableName = $condition = '';

        empty($key) || list($tableName, $condition) = $this->parseKey($key, $and, true, true);

        $tableName = empty($tableName) ? $this->getRealTableName(key($this->table)) : $this->tablePrefix.$tableName;
        empty($tableName) && \Cml\throwException(Lang::get('_PARSE_SQL_ERROR_NO_TABLE_', 'delete'));
        $whereCondition = $this->sql['where'];
        $whereCondition .= empty($condition) ?  '' : (empty($whereCondition) ? 'WHERE ' : '').$condition;
        empty($whereCondition) && \Cml\throwException(Lang::get('_PARSE_SQL_ERROR_NO_CONDITION_', 'delete'));
        $stmt = $this->prepare("DELETE FROM {$tableName} {$whereCondition}", $this->wlink);
        $this->execute($stmt);

        $this->setCacheVer($tableName);
        return $stmt->rowCount();
    }

    /**
     * 获取处理后的表名
     *
     * @param $table
     * @return string
     */
    private function getRealTableName($table)
    {
        return substr($table, strpos($table, '_') + 1);
    }

    /**
     * 根据表名删除数据 这个操作太危险慎用。不过一般情况程序也没这个权限
     *
     * @param string $tableName 要清空的表名
     *
     * @return bool
     */
    public function truncate($tableName)
    {
        $tableName = $this->tablePrefix.$tableName;
        $stmt = $this->prepare("TRUNCATE {$tableName}");

        $this->setCacheVer($tableName);
        return $stmt->execute();//不存在会报错，但无关紧要
    }

    /**
     * 获取多条数据
     *
     * @return array
     */
    public function select()
    {
        $this->sql['columns'] == '' && ($this->sql['columns'] = '*');

        $columns = ($this->sql['columns'] == '*')  ? (
        Config::get('db_fields_cache') ? $this->getDbFields($this->getRealTableName(key($this->table)), null, 1) : '*'
        ) : $this->sql['columns'];

        $table = $operator = $cacheKey = '';
        foreach ($this->table as $key => $val) {
            $realTable = $this->getRealTableName($key);
            $cacheKey .= $this->getCacheVer($realTable);

            $on = null;
            if (isset($this->join[$key])) {
                $operator = ' INNER JOIN';
                $on = $this->join[$key];
            } elseif (isset($this->leftJoin[$key])) {
                $operator = ' LEFT JOIN';
                $on = $this->leftJoin[$key];
            }  elseif (isset($this->rightJoin[$key])) {
                $operator = ' RIGHT JOIN';
                $on = $this->rightJoin[$key];
            } else {
                empty($table) || $operator = ' ,';
            }
            if (is_null($val)) {
                $table .= "{$operator} `{$realTable}`";
            } else {
                $table .= "{$operator} `{$realTable}` AS `{$val}`";
            }
            is_null($on) || $table .= " ON {$on}";
        }

        empty($table) && \Cml\throwException(Lang::get('_PARSE_SQL_ERROR_NO_TABLE_', 'select'));
        empty($this->sql['limit']) && ($this->sql['limit'] = "LIMIT 0, 100");

        $sql = "SELECT $columns FROM {$table} ".$this->sql['where'].$this->sql['groupBy'].$this->sql['having']
            .$this->sql['orderBy'].$this->sql['limit'].$this->union;

        $cacheKey = md5($sql.json_encode($this->bindParams)).$cacheKey;
        $return = Model::getInstance()->cache()->get($cacheKey);
        if ($return === false) {
            $stmt = $this->prepare($sql, $this->rlink);
            $this->execute($stmt);
            $return = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            Model::getInstance()->cache()->set($cacheKey, $return, $this->conf['cache_expire']);
        } else {
            $this->reset();
            $this->bindParams = array();
        }
        return $return;
    }

    /**
     * 返回INSERT，UPDATE 或 DELETE 查询所影响的记录行数。
     *
     * @param $handle \PDOStatement
     * @param int $type 执行的类型1:insert、2:update、3:delete
     *
     * @return int
     */
    public function affectedRows($handle, $type)
    {
        return $handle->rowCount();
    }

    /**
     * 获取上一INSERT的主键值
     *
     * @param \PDO $link
     *
     * @return int
     */
    public function insertId($link = null)
    {
        is_null($link) && $link = $this->wlink;
        return $link->lastInsertId();
    }

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
        $link = '';
        try {
            $host = explode(':', $host);
            $dsn = "mysql:host={$host[0]};".(isset($host[1]) ? "port={$host[1]};" : '')."dbname={$dbName}";
            if ($pConnect) {
                $link = new \PDO($dsn, $username, $password, array(
                    \PDO::ATTR_PERSISTENT => true,
                    \PDO::ATTR_EMULATE_PREPARES=> false
                ));
            } else {
                $link = new \PDO($dsn, $username, $password, array(
                    \PDO::ATTR_EMULATE_PREPARES=> false
                ));
            }
        } catch (\PDOException $e) {
            \Cml\throwException('Pdo Connect Error! Code:'.$e->getCode().',ErrorInfo!:'.$e->getMessage().'<br />');
        }
        $link->exec("SET names $charset");
        if (!empty($engine) && $engine == 'InnoDB') {
            $link->exec('SET innodb_flush_log_at_trx_commit=2');
        }
        return $link;
    }

    /**
     * 指定字段的值+1
     *
     * @param string $key 操作的key user-id-1
     * @param int $val
     * @param string $field 要改变的字段
     *
     * @return bool
     */
    public function increment($key, $val = 1, $field = null)
    {
        list($tableName, $condition) = $this->parseKey($key, true);
        if (is_null($field) || empty($tableName) || empty($condition)) {
            $this->bindParams = array();
            return false;
        }
        $val = abs(intval($val));
        $tableName = $this->tablePrefix.$tableName;

        $stmt = $this->prepare('UPDATE  `'.$tableName."` SET  `{$field}` =  `{$field}` + {$val}  WHERE  $condition");

        $this->execute($stmt);
        $this->setCacheVer($tableName);
        return $stmt->rowCount();
    }

    /**
     * 指定字段的值-1
     *
     * @param string $key 操作的key user-id-1
     * @param int $val
     * @param string $field 要改变的字段
     *
     * @return bool
     */
    public function decrement($key, $val = 1, $field = null)
    {
        list($tableName, $condition) = $this->parseKey($key, true);
        if (is_null($field) || empty($tableName) || empty($condition)) {
            $this->bindParams = array();
            return false;
        }
        $val = abs(intval($val));

        $tableName = $this->tablePrefix.$tableName;
        $stmt = $this->prepare('UPDATE  `'.$tableName."` SET  `$field` =  `$field` - $val  WHERE  $condition");

        $this->execute($stmt);
        $this->setCacheVer($tableName);
        return $stmt->rowCount();
    }

    /**
     * 预处理语句
     *
     * @param string $sql 要预处理的sql语句
     * @param \PDO $link
     * @param bool $resetParams
     *
     * @return \PDOStatement
     */

    public function prepare($sql, $link = null, $resetParams = true)
    {
        $resetParams && $this->reset();
        is_null($link) && $link = $this->wlink;
        if ($GLOBALS['debug']) {
            $bindParams = $this->bindParams;
            foreach ($bindParams as $key => $val) {
                $bindParams[$key] = str_replace('\\\\', '\\', addslashes($val));
            }
            Debug::addTipInfo(vsprintf(str_replace('%s', "'%s'", $sql), $bindParams), 2);
        }

        $sqlParams = array();
        foreach ($this->bindParams as $key => $val) {
            $sqlParams[] = ':param'.$key;
        }
        $tipSql = $sql;
        $sql = vsprintf($sql, $sqlParams);

        $stmt = $link->prepare($sql);//pdo默认情况prepare出错不抛出异常只返回Pdo::errorInfo
        if ($stmt === false) {
            $error = $link->errorInfo();
            $bindParams = $this->bindParams;
            foreach ($bindParams as $key => $val) {
                $bindParams[$key] = str_replace('\\\\', '\\', addslashes($val));
            }
            \Cml\throwException(
                'Pdo Prepare Sql error! ,【Sql : '.vsprintf(str_replace('%s', "'%s'", $tipSql), $bindParams).'】,【Code:'.$link->errorCode ().'】, 【ErrorInfo!:'.$error[2].'】 <br />'
            );
        } else {
            foreach($this->bindParams as $key => $val) {
                is_int($val) ? $stmt->bindValue(':param'.$key, $val, \PDO::PARAM_INT) : $stmt->bindValue(':param'.$key, $val, \PDO::PARAM_STR);
            }
            return $stmt;
        }
        return false;
    }

    /**
     * 执行预处理语句
     *
     * @param object $stmt PDOStatement
     * @param bool $clearBindParams
     *
     * @return bool
     */
    private function execute($stmt, $clearBindParams = true)
    {
        //empty($param) && $param = $this->bindParams;
        $clearBindParams && $this->bindParams = array();
        if (!$stmt->execute()) {
            $error = $stmt->errorInfo();
            \Cml\throwException($error[2]);
        }
        return true;
    }

    /**
     *析构函数
     *
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * 关闭连接
     *
     */
    public function close()
    {
        if (!empty($this->wlink)) {
            Config::get('session_user') || $this->wlink = null; //开启会话自定义保存时，不关闭防止会话保存失败
        }
    }

    /**
     *获取mysql 版本
     *
     *@param \PDO $link
     *
     *@return string
     */
    public function version($link = null)
    {
        is_null($link) && $link = $this->wlink;
        return $link->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * 开启事务
     *
     * @return bool
     */
    public function  startTransAction()
    {
        return $this->wlink->beginTransaction();
    }

    /**
     * 提交事务
     *
     * @return bool
     */
    public function commit()
    {
        return $this->wlink->commit();
    }

    /**
     * 设置一个事务保存点
     *
     * @param string $pointName
     *
     * @return bool
     */
    public function savePoint($pointName)
    {
        return $this->wlink->exec("SAVEPOINT {$pointName}");
    }

    /**
     * 回滚事务
     *
     * @param bool $rollBackTo 是否为还原到某个保存点
     *
     * @return bool
     */
    public function rollBack($rollBackTo = false)
    {
        if ($rollBackTo === false) {
            return $this->wlink->rollBack();
        } else {
            return $this->wlink->exec("ROLLBACK TO {$rollBackTo}");
        }
    }

    /**
     * 调用存储过程
     *
     * @param string $procedureName 要调用的存储过程名称
     * @param array $bindParams 绑定的参数
     * @param bool|true $isSelect 是否为返回数据集的语句
     *
     * @return array|int
     */
    public function callProcedure($procedureName = '', $bindParams = array(), $isSelect = true)
    {
        $this->bindParams = $bindParams;
        $stmt = $this->prepare("exec {$procedureName}");
        $this->execute($stmt);
        if ($isSelect) {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            return  $stmt->rowCount();
        }
    }
}