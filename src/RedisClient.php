<?php

/**
 * +----------------------------------------------------------------------
 * | Redis操作
 * | 支持主机、端口、密码、数据库、前缀等配置
 * | 支持普通连接和持久连接
 * |    如：
 * |        RedisClient::setConfig(['password' => 'HnStcTWffa8XV3J']);
 * |        RedisClient::set('user1', ['name' => '张三', 'age' => 25],3600);
 * |        $user1 = RedisClient::get('user1',true);
 * |
 * |        RedisClient::hSet('user2', 'name', 'dfer');
 * |        $count=RedisClient::hIncrBy('user2', 'counter', 1);
 * |        RedisClient::expire('user2', 30);
 * |        $user2_name = RedisClient::hGet('user2', 'name');
 * |
 * |
 * +----------------------------------------------------------------------
 *                                            ...     .............
 *                                          ..   .:!o&*&&&&&ooooo&; .
 *                                        ..  .!*%*o!;.
 *                                      ..  !*%*!.      ...
 *                                     .  ;$$!.   .....
 *                          ........... .*#&   ...
 *                                     :$$: ...
 *                          .;;;;;;;:::#%      ...
 *                        . *@ooooo&&&#@***&&;.   .
 *                        . *@       .@%.::;&%$*!. . .
 *          ................!@;......$@:      :@@$.
 *                          .@!   ..!@&.:::::::*@@*.:..............
 *        . :!!!!!!!!!!ooooo&@$*%%%*#@&*&&&&&&&*@@$&&&oooooooooooo.
 *        . :!!!!!!!!;;!;;:::@#;::.;@*         *@@o
 *                           @$    &@!.....  .*@@&................
 *          ................:@* .  ##.     .o#@%;
 *                        . &@%..:;@$:;!o&*$#*;  ..
 *                        . ;@@#$$$@#**&o!;:   ..
 *                           :;:: !@;        ..
 *                               ;@*........
 *                       ....   !@* ..
 *                 ......    .!%$! ..     | AUTHOR: dfer
 *         ......        .;o*%*!  .       | EMAIL: df_business@qq.com
 *                .:;;o&***o;.   .        | QQ: 3504725309
 *        .;;!o&****&&o;:.    ..          | WEBSITE: http://www.dfer.site
 * +----------------------------------------------------------------------
 *
 */

namespace Dfer\Tools;

use Exception, stdClass, Redis;

class RedisClient
{
    private $redis;
    private $host;
    private $port;
    private $password;
    private $database;
    private $prefix;
    private $isConnected = false;
    private $transaction = null;
    // 默认配置
    private $defaultConfig = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'database' => 0,
        'prefix' => '',
        'timeout' => 5.0,
        'persistent' => false,
        'persistent_id' => null
    ];

    /**
     * 构造函数
     * @param array $config Redis配置
     * @throws Exception
     */
    public function __construct(array $config = [])
    {
        if($config)
            $this->setConfig($config);
    }

    /**
     * 设置默认参数
     * @param Array $config
     */
    public function setConfig($config)
    {
        // var_dump($config);
        // 合并配置
        $config = array_merge($this->defaultConfig, $config);

        // 检查Redis扩展是否安装
        if (!extension_loaded('redis')) {
            throw new Exception('Redis扩展未安装');
        }

        // 创建Redis实例
        $this->redis = new Redis();

        // 保存配置
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->password = $config['password'];
        $this->database = $config['database'];
        $this->prefix = $config['prefix'];

        // 连接Redis
        $this->connect($config);

        return $this;
    }

    /**
     * 连接到Redis
     * @param array $config 配置数组
     * @return bool
     * @throws Exception
     */
    private function connect(array $config): bool
    {
        try {
            // 判断是否为持久连接
            if ($config['persistent']) {
                $success = $this->redis->pconnect(
                    $config['host'],
                    $config['port'],
                    $config['timeout'],
                    $config['persistent_id']
                );
            } else {
                $success = $this->redis->connect(
                    $config['host'],
                    $config['port'],
                    $config['timeout']
                );
            }

            if (!$success) {
                throw new Exception('无法连接到Redis服务器');
            }

            // 如果有密码，进行认证
            if (!empty($config['password'])) {
                if (!$this->redis->auth($config['password'])) {
                    throw new Exception('Redis认证失败');
                }
            }

            // 测试连接
            if (!$this->ping()) {
                throw new Exception('Redis连接测试失败');
            }

            // 选择数据库
            if ($config['database'] != 0) {
                $this->redis->select($config['database']);
            }

            $this->isConnected = true;
            return true;
        } catch (Exception $e) {
            throw new Exception('Redis连接失败: ' . $e->getMessage());
        }
    }

    /**
     * 检查连接状态
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    /**
     * 获取原始Redis对象
     * @return Redis
     */
    public function getRedis(): Redis
    {
        return $this->redis;
    }

    /**
     * 生成带前缀的键名
     * @param string $key 原始键名
     * @return string
     */
    private function getKey(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * PING服务器，测试连接
     * @return bool
     */
    public function ping(): bool
    {
        try {
            $response = $this->redis->ping();
            return $response === true || $response === '+PONG' || $response === 'PONG';
        } catch (Exception $e) {
            $this->isConnected = false;
            return false;
        }
    }

    /**
     * 开始事务
     * @param int $mode 事务模式，Redis::MULTI 或 Redis::PIPELINE
     * @return $this
     */
    public function multi(int $mode = Redis::MULTI): self
    {
        $this->transaction = $this->redis->multi($mode);
        return $this;
    }

    /**
     * 执行事务中的所有命令
     * @return array|null
     */
    public function exec(): ?array
    {
        if ($this->transaction === null) {
            return null;
        }

        $result = $this->transaction->exec();
        $this->transaction = null;
        return $result;
    }

    /**
     * 取消事务
     * @return bool
     */
    public function discard(): bool
    {
        if ($this->transaction === null) {
            return false;
        }

        $result = $this->transaction->discard();
        $this->transaction = null;
        return $result;
    }

    /**
     * 检查是否在事务中
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->transaction !== null;
    }

    /**
     * 添加字符串值
     * @param string $key 键名
     * @param mixed $value 值
     * @param int $expire 过期时间（秒），0表示不过期
     * @return bool
     */
    public function set(string $key, $value, int $expire = 0)
    {
        $key = $this->getKey($key);

        // 如果是数组或对象，序列化为JSON
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if ($expire > 0) {
            return $this->redis->setex($key, $expire, $value);
        } else {
            return $this->redis->set($key, $value);
        }
    }

    /**
     * 获取字符串值
     * @param string $key 键名
     * @param bool $assoc 是否返回关联数组（当值为JSON时）
     * @return mixed
     */
    public function get(string $key, bool $assoc = false)
    {
        $key = $this->getKey($key);
        $value = $this->redis->get($key);

        if ($value === false) {
            return null;
        }

        // 尝试解码JSON
        $decoded = json_decode($value, $assoc);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $value;
    }

    /**
     * 删除一个或多个键
     * @param string|array $keys 键名或键名数组
     * @return int 删除的数量
     */
    public function delete($keys): int
    {
        if (is_string($keys)) {
            $keys = [$keys];
        }

        // 添加前缀
        $keys = array_map(function ($key) {
            return $this->getKey($key);
        }, $keys);

        return $this->redis->del($keys);
    }

    /**
     * 检查键是否存在
     * @param string $key 键名
     * @return bool
     */
    public function exists(string $key): bool
    {
        $key = $this->getKey($key);
        return $this->redis->exists($key);
    }

    /**
     * 设置键的过期时间
     * @param string $key 键名
     * @param int $seconds 过期时间（秒）
     * @return bool
     */
    public function expire(string $key, int $seconds)
    {
        $key = $this->getKey($key);
        return $this->redis->expire($key, $seconds);
    }

    /**
     * 哈希表 - 设置字段值
     * @param string $key 键名
     * @param string $field 字段名
     * @param mixed $value 值
     * @return bool
     */
    public function hSet(string $key, string $field, $value)
    {
        $key = $this->getKey($key);

        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $this->redis->hSet($key, $field, $value);
    }

    /**
     * 哈希表 - 获取字段值
     * @param string $key 键名
     * @param string $field 字段名
     * @param bool $assoc 是否返回关联数组（当值为JSON时）
     * @return mixed
     */
    public function hGet(string $key, string $field, bool $assoc = false)
    {
        $key = $this->getKey($key);
        $value = $this->redis->hGet($key, $field);

        if ($value === false) {
            return null;
        }

        // 尝试解码JSON
        $decoded = json_decode($value, $assoc);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $value;
    }

    /**
     * 哈希表 - 为哈希表中的字段值加上指定增量值
     * @param string $key 键名
     * @param string $field 字段名
     * @param int $increment 增量值
     * @return int 执行后的字段值
     */
    public function hIncrBy(string $key, string $field, int $increment = 1)
    {
        $key = $this->getKey($key);
        return $this->redis->hIncrBy($key, $field, $increment);
    }

    /**
     * 哈希表 - 为哈希表中的字段值加上指定浮点增量值
     * @param string $key 键名
     * @param string $field 字段名
     * @param float $increment 浮点增量值
     * @return float 执行后的字段值
     */
    public function hIncrByFloat(string $key, string $field, float $increment): float
    {
        $key = $this->getKey($key);
        return $this->redis->hIncrByFloat($key, $field, $increment);
    }

    /**
     * 哈希表 - 获取所有字段和值
     * @param string $key 键名
     * @param bool $assoc 是否解码JSON值
     * @return array
     */
    public function hGetAll(string $key, bool $assoc = false): array
    {
        $key = $this->getKey($key);
        $data = $this->redis->hGetAll($key);

        if (!$assoc || empty($data)) {
            return $data;
        }

        // 解码所有可能为JSON的值
        foreach ($data as &$value) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        return $data;
    }

    /**
     * 哈希表 - 删除一个或多个字段
     * @param string $key 键名
     * @param string|array $fields 字段名或字段名数组
     * @return int
     */
    public function hDelete(string $key, $fields): int
    {
        $key = $this->getKey($key);

        if (is_string($fields)) {
            $fields = [$fields];
        }

        return $this->redis->hDel($key, ...$fields);
    }

    /**
     * 哈希表 - 检查字段是否存在
     * @param string $key 键名
     * @param string $field 字段名
     * @return bool
     */
    public function hExists(string $key, string $field): bool
    {
        $key = $this->getKey($key);
        return $this->redis->hExists($key, $field);
    }

    /**
     * 列表 - 在列表头部插入值
     * @param string $key 键名
     * @param mixed $value 值
     * @return int 列表长度
     */
    public function lPush(string $key, $value): int
    {
        $key = $this->getKey($key);

        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $this->redis->lPush($key, $value);
    }

    /**
     * 列表 - 在列表尾部插入值
     * @param string $key 键名
     * @param mixed $value 值
     * @return int 列表长度
     */
    public function rPush(string $key, $value): int
    {
        $key = $this->getKey($key);

        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $this->redis->rPush($key, $value);
    }

    /**
     * 列表 - 获取列表范围内的元素
     * @param string $key 键名
     * @param int $start 开始索引
     * @param int $end 结束索引
     * @param bool $assoc 是否解码JSON值
     * @return array
     */
    public function lRange(string $key, int $start = 0, int $end = -1, bool $assoc = false): array
    {
        $key = $this->getKey($key);
        $list = $this->redis->lRange($key, $start, $end);

        if (!$assoc || empty($list)) {
            return $list;
        }

        // 解码所有可能为JSON的值
        foreach ($list as &$value) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        return $list;
    }

    /**
     * 集合 - 添加一个或多个成员
     * @param string $key 键名
     * @param mixed $members 成员
     * @return int
     */
    public function sAdd(string $key, ...$members): int
    {
        $key = $this->getKey($key);

        // 序列化数组成员
        foreach ($members as &$member) {
            if (is_array($member) || is_object($member)) {
                $member = json_encode($member, JSON_UNESCAPED_UNICODE);
            }
        }

        return $this->redis->sAdd($key, ...$members);
    }

    /**
     * 集合 - 获取所有成员
     * @param string $key 键名
     * @param bool $assoc 是否解码JSON值
     * @return array
     */
    public function sMembers(string $key, bool $assoc = false): array
    {
        $key = $this->getKey($key);
        $members = $this->redis->sMembers($key);

        if (!$assoc || empty($members)) {
            return $members;
        }

        // 解码所有可能为JSON的值
        foreach ($members as &$member) {
            $decoded = json_decode($member, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $member = $decoded;
            }
        }

        return $members;
    }

    /**
     * 自增
     * @param string $key 键名
     * @param int $value 自增值
     * @return int
     */
    public function incr(string $key, int $value = 1): int
    {
        $key = $this->getKey($key);

        if ($value == 1) {
            return $this->redis->incr($key);
        } else {
            return $this->redis->incrBy($key, $value);
        }
    }

    /**
     * 自减
     * @param string $key 键名
     * @param int $value 自减值
     * @return int
     */
    public function decr(string $key, int $value = 1): int
    {
        $key = $this->getKey($key);

        if ($value == 1) {
            return $this->redis->decr($key);
        } else {
            return $this->redis->decrBy($key, $value);
        }
    }

    /**
     * 获取键的剩余生存时间
     * @param string $key 键名
     * @return int 剩余时间（秒），-1表示永不过期，-2表示键不存在
     */
    public function ttl(string $key): int
    {
        $key = $this->getKey($key);
        return $this->redis->ttl($key);
    }

    /**
     * 清空当前数据库
     * @return bool
     */
    public function flushDB(): bool
    {
        return $this->redis->flushDB();
    }

    /**
     * 获取Redis信息
     * @return array
     */
    public function info(): array
    {
        return $this->redis->info();
    }

    /**
     * 关闭连接
     */
    public function close(): void
    {
        if ($this->isConnected) {
            $this->redis->close();
            $this->isConnected = false;
        }
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->close();
    }
}
