<?php
/**
 * Created By Phpstorm.
 * User: leo
 * Date: 2021/4/17
 * Time: 1:10 下午
 */

require_once __DIR__ . "/../src/Base.php";
require_once __DIR__ . "/../src/Redis.php";

use phpTools\Base;
use phpTools\Redis;

Base::init([]);
var_dump(Redis::getInstance()->setex('test', 60, 1));