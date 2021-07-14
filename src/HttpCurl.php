<?php
/**
 * Created By Phpstorm.
 * User: leo
 * Date: 2021/7/13
 * Time: 下午11:45
 */

namespace phpTools;

use phpTools\traits\Instance;

class HttpCurl
{
    use Instance;

    /**
     * GET请求
     * @param string $url
     * @param array $params
     * @param int $timeout
     * @return bool|string
     */
    public function get(string $url, array $params = [], int $timeout = 5)
    {
        if (!$url || $timeout <= 0) {
            return false;
        }
        if ($params) {
            $url = $url . '?' . http_build_query($params);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /**
     * POST请求
     * @param string $url
     * @param array $params
     * @param int $timeout
     * @return bool|string
     */
    public function post(string $url, array $params = [], int $timeout = 5)
    {
        if (!$url || $timeout <= 0) {
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        //curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

}