<?php
/**
 ** RAYSWOOLE [ HIGH PERFORMANCE CMS BASED ON SWOOLE ]
 ** ----------------------------------------------------------------------
 ** Copyright © 2020 http://haoguangyun.com All rights reserved.
 ** ----------------------------------------------------------------------
 ** Author: haoguangyun <admin@haoguangyun.com>
 ** ----------------------------------------------------------------------
 ** Last-Modified: 2020-08-13 09:38
 ** ----------------------------------------------------------------------
 **/

namespace rayswoole\redis\facade;


use Swoole\Coroutine;
use rayswoole\redis\RedisPool;

/**
 * class Redis
 * @package rayswoole
 * @mixin \Redis
 */
class Redis
{
    /**
     * @var RedisPool
     */
    private static $init;
    /**
     * @var \Redis
     */
    private static $instance;

    /**
     * 初始化连接池
     * @param array $config
     * @return RedisPool 连接池对象
     */
    static function init(\rayswoole\redis\RedisConfig $config = null)
    {
        if (!isset(self::$init) && is_object($config)){
            self::$init = new RedisPool($config);
        }
        return self::$init;
    }

    /**
     * 获取一个连接对象
     * @return \Redis
     * @throws \Throwable
     */
    static function getInstance()
    {
        if (Coroutine::getCid() > 0) {
            if (!isset(Coroutine::getContext()['Redis_Instance'])) {
                Coroutine::getContext()['Redis_Instance'] = self::$init->defer();
            }
            return Coroutine::getContext()['Redis_Instance'];
        } else {
            if (!isset(self::$instance)) {
                self::$instance = self::$init->defer();
            }
            return self::$instance;
        }
    }

    public static function __callStatic($method, $params)
    {
        return call_user_func_array([static::getInstance(), $method], $params);
    }
}