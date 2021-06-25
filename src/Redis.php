<?php
/**
 * Created By Phpstorm.
 * User: leo
 * Date: 2021/4/17
 * Time: 10:59 上午
 */

namespace phpTools;

class Redis extends Base
{
    /** @var \Redis */
    private static $instance = null; //redis实例
    private static $handler = null;  //连接句柄

    private function __construct()
    {
        $option = self::$config['redis'];
        if (empty($option) || !is_array($option)) {
            throw new \Exception('Redis config error');
        }
        if (!extension_loaded('redis')) {
            throw new \Exception('not support: Redis');
        }

        self::$handler = new \Redis();
        self::$handler->connect($option['host'], $option['port'], $option['timeout']);
        if ($option['password']) {
            self::$handler->auth($option['password']);
        }
        if ($option['db']) {
            self::$handler->select($option['db']);
        }
        if ($option['prefix']) {
            self::$handler->setOption(\Redis::OPT_PREFIX, $option['prefix']);
        }
    }

    /**
     * 返回单例redis对象
     * @return Redis
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * redis加锁
     * @param string $sign 标识
     * @param int $expire 加锁时间(s)
     * @param int $ttl 过期时间
     * @param string $prefix
     * @return bool
     */
    public static function redisLock(string $sign, int $expire = 1, int $ttl = 300, string $prefix = 'lock_')
    {
        $key = self::lockKey($sign, $prefix);
        $redis = self::getInstance();
        $isLock = $redis->setnx($key, (time() + $expire));
        // 不能获取锁
        if (!$isLock) {
            // 判断锁是否过期
            $lockTime = $redis->get($key);
            if (time() > $lockTime) {
                self::redisUnLock($sign, $prefix);
                $isLock = $redis->setnx($key, (time() + $expire));
            }
        }
        if ($ttl > 0 && $isLock) {
            $redis->expire($key, $ttl);
        }
        return $isLock ? true : false;
    }

    /**
     * redis释放锁
     * @param string $sign
     * @param string $prefix
     * @return int
     */
    public static function redisUnLock(string $sign, string $prefix = 'lock_')
    {
        return self::getInstance()->del(self::lockKey($sign, $prefix));
    }

    /**
     * 访问频率限制
     * @param string $sign 标识
     * @param int $time 单位时间s
     * @param int $limit 限制次数
     * @param string $prefix
     * @return bool
     */
    public static function limitRequest(string $sign, $time = 5, $limit = 3, $prefix = 'limit_')
    {
        $key = $prefix . $sign;
        $redis = self::getInstance();
        if ($redis->exists($key)) {
            list($lastTime, $count) = explode('|', $redis->get($key));
        } else {
            list($lastTime, $count) = [time(), $limit];
        }

        $count = (int)($limit / $time * (time() - $lastTime) + $count); //过去单位时间,补上对应次数
        if ($count < 1) {
            return false;
        }
        $count = min($count, $limit);
        return $redis->setex($key, $time, time() . '|' . --$count);
    }

    /**
     * 查询不同类型数据
     * @param string $key
     * @param int $isDel
     * @return array
     */
    public static function optQuery($key, $isDel = 0)
    {
        $redis = self::getInstance();
        if (!$redis->exists($key)) {
            return [];
        }
        if ($isDel) {
            return [$redis->del($key)];
        }
        $type = $redis->type($key);
        switch ($type) {
            case 1: //string
                $content = $redis->get($key);
                break;
            case 2: //set
                $content = ['total' => $redis->sCard($key), 'content' => $redis->sMembers($key)];
                break;
            case 3: //list
                $content = ['total' => $redis->lLen($key), 'content' => $redis->lRange($key, 0, 99)];
                break;
            case 4: //zSet
                $content = ['total' => $redis->zCard($key), 'content' => $redis->zRange($key, 0, 99, true)];
                break;
            case 5: //hash
                $content = ['total' => $redis->hLen($key), 'content' => $redis->hGetAll($key)];
                break;
            default:
                $content = '';
                break;
        }
        $arr = [1 => 'string', 2 => 'set', 3 => 'list', 4 => 'zSet', 5 => 'hash'];
        return ['type' => $arr[$type], 'ttl' => $redis->ttl($key), 'data' => $content];
    }

    /**
     * 生成锁KEY
     * @param string $sign
     * @param string $prefix
     * @return string
     */
    private static function lockKey(string $sign, string $prefix)
    {
        return $prefix . md5($sign);
    }

    public function __call($method, $arguments)
    {
        if (method_exists(self::$handler, $method)) {
            return call_user_func_array([self::$handler, $method], $arguments);
        } else {
            throw new \Exception("Redis method {$method} doesn't exists");
        }
    }

    public function __clone()
    {
    }

    public function __wakeup()
    {
    }
}