<?php
namespace Cml\Lock;

class File extends Base
{
    /**
     * 使用的缓存
     *
     * @var string
     */
    private $userCache = 'default_cache';

    public function __construct($userCache){
        is_null($userCache) || $this->userCache = $userCache;
    }

    /**
     * 上锁
     *
     * @param string $key
     * @param bool $wouldblock 是否堵塞
     *
     * @return mixed
     */
    public function lock($key, $wouldblock = false)
    {
        if(empty($key)) {
            return false;
        }

        $fileName = $this->getFileName($key);
        if(!$fp = fopen($fileName, 'w+')) {
            return false;
        }

        if (flock($fp, LOCK_EX)) {
            self::$lockCache[$fileName] = $fp;
            return true;
        }

        //非堵塞模式
        if (!$wouldblock) {
            self::$lockCache[$fileName] = 0;
            return false;
        }

        //堵塞模式
        do {
            usleep(200);
        } while (!flock($fp, LOCK_EX));

        self::$lockCache[$fileName] = $fp;
        return true;
    }

    /**
     * 解锁
     *
     * @param string $key
     */
    public function unlock($key) {
        $fileName = $this->getFileName($key);

        if (isset(self::$lockCache[$fileName]) && self::$lockCache[$fileName]) {
            is_file($fileName) && unlink($fileName);
            fclose(self::$lockCache[$fileName]);
            unset(self::$lockCache[$fileName]);
        }
    }

    /**
     * 定义析构函数 自动释放获得的锁
     */
    public function __destruct() {
        foreach (self::$lockCache as $key => $islock) {
            if ($islock) {
                if (is_file($key)) {
                    fclose($islock);
                    unlink($key);
                }
            }
        }
    }

    /**
     * 获取缓存文件名
     *
     * @param  string $key 缓存名
     *
     * @return string
     */
    private function getFileName($key)
    {
        $md5Key = md5($this->getKey($key));

        $dir = \CML_RUNTIME_CACHE_PATH.DIRECTORY_SEPARATOR.'LockFileCache'.DIRECTORY_SEPARATOR . substr($key, 0, strrpos($key, '/')) . DIRECTORY_SEPARATOR;
        $dir .=  substr($md5Key, 0, 2) . DIRECTORY_SEPARATOR . substr($md5Key, 2, 2);
        is_dir($dir) || mkdir($dir, 0700, true);
        return  $dir.DIRECTORY_SEPARATOR. $md5Key . '.php';
    }
}