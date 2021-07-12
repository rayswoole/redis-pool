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
     * @var int 序列化选项
     */
    private static $OPT_SERIALIZER = 0;

    /**
     * 初始化连接池
     * @param array $config
     * @return RedisPool 连接池对象
     */
    static function init(\rayswoole\redis\RedisConfig $config = null)
    {
        if (!isset(self::$init) && is_object($config)){
            self::$init = new RedisPool($config);
            if (!empty($config->getExtraConf()['options']) && !empty($config->getExtraConf()['options'][\Redis::OPT_SERIALIZER])) {
                static::$OPT_SERIALIZER = $config->getExtraConf()['options'][\Redis::OPT_SERIALIZER];
            }
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
        if (!isset(static::$instance)){
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function getMultiple(array $keys)
    {
        $ret = self::$init->defer()->multi();
        foreach ($keys as $key){
            $ret->get($key);
        }
        return $ret->exec();
    }

    public function setMultiple(array $values, $ttl = 0)
    {
        $ret = self::$init->defer()->multi();
        foreach ($values as $key=>$value){
            $ret->set($key, $value, $ttl ?: null);
        }
        $ret->exec();
    }

    public function deleteMultiple($keys)
    {
        if (!is_array($keys)){
            return false;
        }
        return self::$init->defer()->unlink($keys);
    }

    public function has($key)
    {
        return !empty(self::$init->defer()->exists($key));
    }

    public function get($key)
    {
        $data = self::$init->defer()->get($key);
        if (!empty($data) && static::$OPT_SERIALIZER === 0 && is_string($data) && $data[0] === 'a' && $data[1] === ':') {
            $undata = unserialize($data);
            return $undata === false ? $data : $undata;
        }
        return $data;

    }

    public function set($key, $value, $ttl = null)
    {
        $time = time();
        if ($ttl > $time){
            $ttl = $ttl - $time;
        }
        if (static::$OPT_SERIALIZER === 0 && !is_string($value) && !is_numeric($value) && !is_bool($value)) {
            return self::$init->defer()->set($key, serialize($value), $ttl);
        } else {
            return self::$init->defer()->set($key, $value, $ttl);
        }
    }

    public function inc($key, $offset = 1)
    {
        return self::$init->defer()->incrBy($key, $offset);
    }

    public function dec($key, $offset = 1)
    {
        return self::$init->defer()->decrBy($key, $offset);
    }

    public function delete($key)
    {
        return self::$init->defer()->del($key);
    }

    public function clear()
    {
        return self::$init->defer()->flushDB();
    }

    public function isnull($value)
    {
        return $value === false;
    }

    public function __call($method, $params)
    {
        if (isset(self::$init)){
            return call_user_func_array([self::$init->defer(), $method], $params);
        } else {
            throw new \Exception('Redis instance is not configured');
        }

    }

    public static function __callStatic($method, $params)
    {
        if (isset(self::$init)){
            return call_user_func_array([self::$init->defer(), $method], $params);
        } else {
            throw new \Exception('Redis instance is not configured');
        }
    }
}