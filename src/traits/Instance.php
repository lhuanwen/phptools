<?php
/**
 * Created By Phpstorm.
 * User: leo
 * Date: 2021/7/13
 * Time: 下午11:54
 */

namespace phpTools\traits;

trait Instance
{
    private static $instance = null;

    /**
     * @return Instance
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}