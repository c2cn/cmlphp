<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  2.5
 * cml框架 系统默认Model
 * *********************************************************** */
namespace Cml;

/**
 * 基础Model类，在CmlPHP中负责数据的存取(目前包含db/cache)
 * @package Cml
 */
class Model
{
    /**
     * @var null 表名
     */
    protected $table = null;

    /**
     * @var array Db驱动实例
     */
    private static $dbInstance = array();

    /**
     * @var array Cache驱动实例
     */
    private static $cacheInstance = array();

    /**
     * 获取db实例
     *
     * @param string $conf 使用的数据库配置;
     *
     * @return \Cml\Db\MySql\Pdo | \Cml\Db\MongoDB\MongoDB
     */
    public function db($conf = '')
    {
        $conf == '' &&  $conf = $this->getDbConf();
        $config = is_array($conf) ? $conf : Config::get($conf);
        $driver = '\Cml\Db\\'.str_replace('.', '\\', $config['driver']);
        if (isset(self::$dbInstance[$conf])) {
            return self::$dbInstance[$conf];
        } else {
            self::$dbInstance[$conf] = new $driver($config);
            return self::$dbInstance[$conf];
        }
    }

    /**
     * 当程序连接N个db的时候用于释放于用连接以节省内存
     *
     * @param string $conf 使用的数据库配置;
     */
    public function closeDb($conf = 'default_db')
    {
        //$this->db($conf)->close();释放对象时会执行析构回收
        unset(self::$dbInstance[$conf]);
    }

    /**
     * 获取cache实例
     *
     * @param string $conf 使用的缓存配置;
     *
     * @return \Cml\Cache\Redis | \Cml\Cache\Apc | \Cml\Cache\File | \Cml\Cache\Memcache
     */
    public function cache($conf = 'default_cache')
    {
        $config = is_array($conf) ? $conf : Config::get($conf);
        $driver = '\Cml\Cache\\'.$config['driver'];
        if (isset(self::$cacheInstance[$conf])) {
            return self::$cacheInstance[$conf];
        } else {
            if ($config['on']) {
                self::$cacheInstance[$conf] = new $driver($config);
                return self::$cacheInstance[$conf];
            } else {
                throwException(Lang::get('_NOT_OPEN_', $conf));
                return false;
            }
        }
    }

    /**
     * 初始化一个Model实例
     *
     * @return \Cml\Model
     */
    public static function getInstance()
    {
        static $mInstance = array();
        $class = get_called_class();
        if (!isset($mInstance[$class])) {
            $mInstance[$class] = new $class();
        }
        return $mInstance[$class];
    }

    /**
     * 获取表名
     *
     * @return null|string
     */
    protected function getTableName()
    {
        if (is_null($this->table)) {
            $tmp = get_class($this);
            $this->table = strtolower(substr($tmp, strrpos($tmp, '\\') + 1, -5));
        }
        return $this->table;
    }

    /**
     * 通过主键获取单条数据-快捷方法
     *
     * @param mixed $val 值
     * @param string $column 字段名 不传会自动分析表结构获取主键
     *
     * @return bool|array
     */
    public function getByPk($val, $column = null)
    {
        return $this->getByColumn($val, $column);
    }

    /**
     * 通过某个字段获取单条数据-快捷方法
     *
     * @param mixed $val 值
     * @param string $column 字段名 不传会自动分析表结构获取主键
     *
     * @return bool|array
     */
    public function getByColumn($val, $column = null)
    {
        is_null($column) && $column = $this->db($this->getDbConf())->getPk($this->getTableName());
        $data = $this->db($this->getDbConf())->table($this->getTableName())
            ->where($column, $val)
            ->limit(0, 1)
            ->select();
        if (isset($data[0])) {
            return $data[0];
        } else {
            return false;
        }
    }

    /**
     * 增加一条数据-快捷方法
     *
     * @param $data
     *
     * @return int
     */
    public function set($data){
        return $this->db($this->getDbConf())->set($this->getTableName(), $data);
    }

    /**
     * 通过主键更新一条数据-快捷方法
     *
     * @param int $val 主键id
     * @param array $data
     * @param string $column 字段名 不传会自动分析表结构获取主键
     *
     * @return bool
     */
    public function updateByPk($val, $data, $column = null){
        return $this->updateByColumn($val, $data, $column);
    }

    /**
     * 通过字段更新一条数据-快捷方法
     *
     * @param int $val 主键id
     * @param array $data
     * @param string $column 字段名 不传会自动分析表结构获取主键
     *
     * @return bool
     */
    public function updateByColumn($val, $data, $column = null){
        is_null($column) && $column = $this->db($this->getDbConf())->getPk($this->getTableName());
        return $this->db($this->getDbConf())->where($column, $val)
            ->update($this->getTableName(), $data);
    }

    /**
     * 通过主键删除一条数据-快捷方法
     *
     * @param mixed $val
     * @param string $column 字段名 不传会自动分析表结构获取主键
     *
     * @return bool
     */
    public function delByPk($val, $column = null)
    {
        return $this->delByColumn($val, $column);
    }

    /**
     * 通过主键删除一条数据-快捷方法
     *
     * @param mixed $val
     * @param string $column 字段名 不传会自动分析表结构获取主键
     *
     * @return bool
     */
    public function delByColumn($val, $column = null)
    {
        is_null($column) && $column = $this->db($this->getDbConf())->getPk($this->getTableName());
        return $this->db($this->getDbConf())->where($column, $val)
            ->delete($this->getTableName());
    }

    /**
     * 获取数据的总数
     *
     * @param null $pkField 主键的字段名
     *
     * @return mixed
     */
    public function getTotalNums($pkField = null)
    {
        is_null($pkField) && $pkField = $this->db($this->getDbConf())->getPk($this->getTableName());
        return $this->db($this->getDbConf())->table($this->getTableName())->count($pkField);
    }

    /**
     * 获取数据列表
     *
     * @param int $start
     * @param int $limit
     * @param string|array $order
     *
     * @return array
     */
    public function getList($start = 0, $limit = 20, $order = 'DESC')
    {
        is_array($order) || $order = array($this->db($this->getDbConf())->getPk($this->getTableName()) => $order);

        $dbInstance = $this->db($this->getDbConf())->table($this->getTableName());
        foreach($order as $key => $val)  {
            $dbInstance->orderBy($key, $val);
        }
        return $dbInstance->limit($start, $limit)
            ->select();
    }

    /**
     * 获取当前Model的数据库配置串
     *
     * @return string
     */
    public function getDbConf()
    {
        return property_exists($this, 'db') ? $this->db : 'default_db';
    }
}