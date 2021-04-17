<?php
/**
 * Created By Phpstorm.
 * User: leo
 * Date: 2021/4/17
 * Time: 12:49 下午
 */

namespace phpTools;

class Base
{
    protected static $config = [
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => '',
            'db' => 0,
            'timeout' => 5,
            'prefix' => 'dev_'
        ]
    ];

    public static function init($config = [])
    {
        self::$config = array_merge(self::$config, $config);
    }
}