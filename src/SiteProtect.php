<?php

/**
 * +----------------------------------------------------------------------
 * | 网站防护
 * | 包含频率限制、用户代理检测、JavaScript挑战
 * | 限制爬虫随意爬取网站内容。随着ai的发展，爬虫越来越不可控，严重影响中小网站的正常访问，需要重视
 * | 如：
 * |    SiteProtect::setConfig(['redis_password' => 'HnStcTWffa8XV3J', 'rate_limit' => 9, 'rate_window' => 60])->protect();
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

class SiteProtect extends Common
{
    private $debug = true;
    private $serverCacheKey;
    private $redis;
    private $config = [
        // Redis配置
        'redis_host' => '127.0.0.1',
        'redis_port' => 6379,
        'redis_password' => null,

        // 频率限制配置
        'rate_limit_enabled' => true,
        'rate_limit' => 9,      // 限制次数。在时间窗口内允许的最大请求次数
        'rate_window' => 3600,    // 时间窗口。统计请求的时间范围（单位：秒）

        // UA检测配置
        'ua_check_enabled' => true,
        'whitelist_uas' => [
            "baiduspider" => "百度搜索",
            "360spider" => "360搜索",
            "sogou" => "搜狗搜索",
            "yisouspider" => "神马搜索",
            "petalbot" => "华为搜索",
            "bytespider" => "字节跳动",
            "googlebot" => "Google搜索",
            "mediapartners-google" => "Google广告",
            "bingbot" => "Bing搜索",
            "yahoo" => "Yahoo!搜索",
            "mail.ru_bot" => "俄罗斯搜索",
            "seznambot" => "捷克搜索"
        ], //ua白名单。不区分大小写

        // JS挑战配置
        'js_challenge_enabled' => true,
        'js_challenge_key' => 'js_verified', //cookie键名
        'js_challenge_ttl' => 1800, // cookie缓存时间。默认：30分钟
    ];

    // ********************** 初始化 START **********************

    /**
     * 构造函数
     * @param array $config 配置数组
     */
    public function __construct($config = [])
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
        $this->config = array_merge($this->config, $config);
        $this->initRedis();
        return $this;
    }

    /**
     * 初始化Redis连接
     */
    private function initRedis()
    {
        if (!class_exists('Redis')) {
            $this->logSiteProtect('频率限制检测', 'Redis扩展未安装，使用文件缓存降级');
            return;
        }

        try {
            $this->redis = new Redis();
            $connected = $this->redis->connect(
                $this->config['redis_host'],
                $this->config['redis_port'],
                1 // 1秒超时
            );

            if ($connected && $this->config['redis_password']) {
                $this->redis->auth($this->config['redis_password']);
            }

            // 测试连接
            $this->redis->ping();
        } catch (Exception $e) {
            $this->logSiteProtect('频率限制检测', 'Redis连接失败: ' . $e->getMessage());
            $this->redis = null;
        }
    }

    // **********************  初始化 END  **********************

    /**
     * 综合防护检查
     * 所有请求都有频率限制
     * ua白名单之外的请求需要接受js验证。除了允许的搜索引擎爬虫之外，所有的请求都必须通过网页访问
     */
    public function protect()
    {
        $ban = false;

        // 1. 频率限制
        list($rateOk, $remaining, $reset) = $this->rateLimit();
        // var_dump($rateOk, $remaining, $reset);
        if (!$rateOk) {
            $ban = true;
        }

        // 2. UA检测
        if (!$this->checkUserAgent()) {
            // 3. JS验证
            if (!$this->checkJS()) {
                $ban = true;
            }
        }

        if ($ban) {
            header('HTTP/1.1 403 Dfer.Site');
            exit('禁止访问');
        }
    }

    // ********************** 频率限制 START **********************

    /**
     * 频率限制检查
     * @param string $identifier 标识符(默认使用IP)
     * @return array [是否通过, 剩余次数, 重置剩余时间（秒）]
     */
    public function rateLimit($identifier = null)
    {
        if (!$this->config['rate_limit_enabled']) {
            return [true, PHP_INT_MAX, 0];
        }

        $identifier = $identifier ?: $this->getClientIP();
        // 特定时间范围内共用一个时间窗口值
        $windowKey = floor(time() / $this->config['rate_window']);
        $this->serverCacheKey = "rate:{$identifier}:{$windowKey}";

        if ($this->redis) {
            // Redis - O(1)时间复杂度
            $pipe = $this->redis->multi(Redis::PIPELINE);
            // 累加。不存在则先创建并设为0再加1
            $pipe->hIncrBy($this->serverCacheKey, 'count', 1);
            // 设置过期时间
            $pipe->expire($this->serverCacheKey, $this->config['rate_window']);
            // 执行管道中的所有命令。返回一个数组，包含每个命令的返回值
            $result = $pipe->exec();
            $count = $result[0];
        } else {
            // 文件缓存 - O(n)但更简单
            $filePath = sys_get_temp_dir() . '/' . md5($this->serverCacheKey) . '.cnt';
            // var_dump($filePath);
            $count = 1;

            if (file_exists($filePath)) {
                $data = explode(':', file_get_contents($filePath));
                if (time() - $data[1] < $this->config['rate_window']) {
                    $count = $data[0] + 1;
                }
            }

            file_put_contents($filePath, "{$count}:" . time());

            // 清理旧文件(概率性清理，避免性能问题)
            if (mt_rand(1, 100) === 1) {
                $this->cleanOldFiles(sys_get_temp_dir(), $this->config['rate_window']);
            }
        }

        $remaining = max(0, $this->config['rate_limit'] - $count);
        $resetTime = ($windowKey + 1) * $this->config['rate_window'];

        $this->generateJS();

        return [
            $count <= $this->config['rate_limit'],
            $remaining,
            $resetTime - time()
        ];
    }

    /**
     * 清理旧文件
     */
    private function cleanOldFiles($dir, $maxAge)
    {
        $files = glob($dir . '/*.{cnt,js}', GLOB_BRACE);
        $now = time();

        foreach ($files as $file) {
            if ($now - filemtime($file) > $maxAge) {
                @unlink($file);
            }
        }
    }

    // **********************  频率限制 END  **********************

    // ********************** UA检测 START **********************

    /**
     * 用户代理检测
     * 由于ua可以轻易伪装，千变万化，通过长度或特定字符串来封禁会无穷无尽，防不胜防，稍微改一下ua就能绕过限制，所以，设置白名单才是性价比最高的做法
     * @return bool 是否允许访问
     */
    public function checkUserAgent()
    {
        if (!$this->config['ua_check_enabled']) {
            return true;
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // var_dump($userAgent);
        foreach ($this->config['whitelist_uas'] as $ua=>$title) {
            // 不区分大小写
            if (stripos(strtolower($userAgent), strtolower($ua)) !== false) {
                // $this->logSiteProtect('UA检测', $userAgent);
                return true;
            }
        }

        return false;
    }

    // **********************  UA检测 END  **********************

    // ********************** JS验证 START **********************

    /**
     * 生成JavaScript挑战
     */
    private function generateJS()
    {
        // 当前运行次数
        $count = $this->redis->hGet($this->serverCacheKey, 'count');
        if ($this->config['js_challenge_enabled'] && $count == 1) {
            // 随机生成token
            $challengeId = bin2hex(random_bytes(8));
            $token = hash('sha256', $challengeId . microtime());
            // 设置token
            $this->redis->hSet($this->serverCacheKey, 'token', $token);

            $cookieKey = $this->config['js_challenge_key'];
            // 生成JS代码
            $js = <<<STR
            <script>
            (function(){
                var t='{$token}';
                var d=new Date();
                d.setTime(d.getTime()+1800000);
                document.cookie='{$cookieKey}='+t+'; expires='+d.toUTCString()+'; path=/';
            })();
            </script>
            STR;
            echo $js;
        }
    }

    /**
     * 检查Cookie中的JS验证
     * 由于后端执行完成才会运行前端，js的生效会始终比php滞后一步，所以，要让redis和cookie同时储存token，从第二次请求开始用上一次的redis与这一次的cookie进行比对
     * @return bool 是否通过验证
     */
    public function checkJS()
    {
        $count = $this->redis->hGet($this->serverCacheKey, 'count');
        if ($this->config['js_challenge_enabled'] && $count > 1) {
            $cookieKey = $this->config['js_challenge_key'];
            if (isset($_COOKIE[$cookieKey]) && strlen($_COOKIE[$cookieKey]) === 64) {
                $storedToken = $_COOKIE[$cookieKey] ?? false;
                $token = $this->redis->hGet($this->serverCacheKey, 'token');
                // 安全地比较字符串
                return $storedToken && hash_equals($storedToken, $token);
            }
        } else
            return true;
    }

    // **********************  JS验证 END  **********************

    /**
     * 获取客户端IP
     * @return string IP地址
     */
    private function getClientIP()
    {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * 日志
     */
    private function logSiteProtect($type, $data)
    {
        $log = sprintf(
            "[%s] %s - IP: %s - UA: %s - Data: %s\n",
            date('Y-m-d H:i:s'),
            $type,
            $this->getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            substr($data, 0, 200)
        );

        parent::logSiteProtect($log);
    }
}
