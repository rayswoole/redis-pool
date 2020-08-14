# rayswoole/redis-pool

基于PHP7.1+ 和 phpredis扩展 实现的redis连接池：

* 实现了redis的连接池, 减少频繁建立连接的开销
* 支持phpredis的单机模式和Cluster模式
* 支持自动保持连接
* 支持保持最小空闲连接数量, 满足突发连接
* 弹性伸缩

## 安装
~~~
composer require rayswoole/redis-pool
~~~

## 文档

### 连接池配置
~~~
可以在onStart直接配置
每个worker进程都会生成同等配置的进程池, 请根据worker数量动态调整
~~~

```php
//初始化连接配置
$redisConfig = new \rayswoole\redis\RedisConfig();
//设置最小闲置连接数
$redisConfig->withMin(20);
//设置最大连接数
$redisConfig->withMax(100);
//设置定时器执行频率(毫秒),创建最小空间连接、回收空闲连接
$redisConfig->withIntervalTime(15*1000)
//设置连接可空闲时间
$redisConfig->withIdleTime(30)
//获取连接池对象超时时间, 如果连接池占满在指定时间无法释放新的连接, 将输出Exception, 需要自行捕获
$redisConfig->withTimeout(3.0)
//数据库配置注入
$redisConfig->withExtraConf('redis配置')
//初始化连接池
\rayswoole\redis\facade\Redis::init($redisConfig);
```

### redis 配置结构
```php
$config = [
    'server' => ['127.0.0.1:6379','....'],//多IP时为cluster模式
    'dbIndex' => 0,//数据库序号, 不建议变更
    'username'=>'',//适用于账号密码认证
    'password'=>'',//适用于账号密码/密码认证
    'prefix' => 'ray_',//key前缀
    'writeOnly' => false,//主服务器是否只写
    'options' => [],
];
```

### 进程池使用示例
```php
use \rayswoole\redis\facade\Redis;
Redis::getInstance()->set('key', 'value');//带编辑器提示
//或者
Redis::set('key', 'value');//不带提示

```

### 不用进程池单独连接
```php
use rayswoole\redis;
RedisClient::get($redisConfig->getExtraConf());
```

