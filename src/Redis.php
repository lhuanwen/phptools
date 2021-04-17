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