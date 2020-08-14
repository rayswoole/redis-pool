<?php
/**
 ** RAYSWOOLE [ HIGH PERFORMANCE CMS BASED ON SWOOLE ]
 ** ----------------------------------------------------------------------
 ** Copyright © 2020 http://haoguangyun.com All rights reserved.
 ** ----------------------------------------------------------------------
 ** Author: haoguangyun <admin@haoguangyun.com>
 ** ----------------------------------------------------------------------
 ** Last-Modified: 2020-08-14 18:07
 ** ----------------------------------------------------------------------
 **/

namespace rayswoole\redis;

class RedisClient
{
    /**
     * 连接Redis
     * @param $conf
     * @return \Redis|\RedisCluster
     * @throws \Exception
     */
    static function get($conf)
    {
        if (count($conf['server']) == 1){
            $server = $conf['server'][0];
            $arr = explode(':', $server);
            $host = $arr[0] ?: '127.0.0.1';
            $port = $arr[1] ?? 6739;

            $redis = new \Redis();
            if ($host{0} == '/'){//.sock模式
                $connect = $redis->connect($host);
            } else {
                $connect = $redis->connect($host, $port);
            }
            if (!$connect){
                throw new \Exception('Redis Pool Connection reconnect failed');
            }
            if (!empty($conf['password'])){
                $auth['pass'] = $conf['password'];
                if (!empty($conf['username'])){
                    $auth['user'] = $conf['username'];
                }
                try{
                    $redis->auth($auth);
                }catch (\RedisException $exception){
                    throw new \Exception($exception->getMessage());
                }
            }
            $redis->select(is_int($conf['dbIndex']) && $conf['dbIndex']<10 ? $conf['dbIndex'] : 0);
            $redis->setOption(\Redis::OPT_PREFIX, $conf['prefix']);
            if (is_array($conf['options'])){
                foreach ($conf['options'] as $option=>$value){
                    $redis->setOption($option, $value);
                }
            }
            return $redis;
        } else {
            $redis = new \RedisCluster(NULL, $conf['server'],$this->conf->getTimeout(),$this->conf->getTimeout(), true);
            $redis->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_ERROR);
            if (empty($conf['writeOnly'])){
                $redis->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_DISTRIBUTE);
            } else {
                $redis->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_DISTRIBUTE_SLAVES);
            }
            $redis->select(is_int($conf['database']) && $conf['database']<10 ? $conf['database'] : 0);
            $redis->setOption(Redis::OPT_PREFIX, $conf['prefix']);
            if (is_array($conf['options'])){
                foreach ($conf['options'] as $option=>$value){
                    $redis->setOption($option, $value);
                }
            }
            return $redis;
        }
    }
}