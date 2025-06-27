<?php

/**
 * +----------------------------------------------------------------------
 * | thinkphp8 常用的方法
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

use Exception, Closure;
use think\db\exception\PDOException;
use Dfer\Tools\Constants;

class Tp8 extends Common
{
    private $debug = false;
    // 数据库模型
    private $db_model = null;
    // 日志文件路径
    private $log_file = "";

    public function __construct()
    {
        // 取消 PHP 最大执行时间限制
        set_time_limit(0);
        $root = $this->getRootPath();
        $this->log_file = $this->str("{root}/data/logs/{dir}/{file}.tp8.log", ["root" => $root, "dir" => date('Ym'), "file" => date('d')]);
        ini_set('error_log', $this->log_file);
    }

    /**
     * 设置默认参数
     * @param Array $config
     */
    public function setDefaultConfig($config)
    {
        $this->debug = $config['debug'] ?? $this->debug;
        $this->db_model = $config['db_model'] ?? $this->db_model;
        return $this;
    }

    // ###################################### 数据库 START ######################################

    /**
     * 带容错机制的数据库操作
     * 数据库连接出现问题，则自动重连，重新执行数据库操作代码，达到上限以后记录详细错误数据
     * @param {Object} Closure $callback 回调函数。数据库操作代码
     * @param {Object} int $maxRetries 重试次数上限
     * @return mixed
     */
    public function runWithRetry(Closure $callback, int $maxRetries = 9)
    {
        $retryCount = 0;
        $lastError = null;

        if ($this->debug)
            echo ini_get('error_log') . PHP_EOL;

        while ($retryCount < $maxRetries) {
            try {
                return $callback();
            } catch (PDOException $e) {
                $retryCount++;
                $lastError = $e;

                // 记录错误
                $this->logDatabaseError($e, $retryCount, $maxRetries);

                if ($this->debug)
                    echo "重试 {$retryCount}/{$maxRetries}" . PHP_EOL;
                // 以微秒为单位进行延迟。退避策略：1000ms, 2000ms ...
                usleep(1000 * 1000 * $retryCount);
            }
        }

        // 所有重试失败后的处理
        throw new Exception(
            "数据库操作失败，已重试 {$retryCount} 次: " . $lastError->getMessage(),
            $lastError->getCode(),
            $lastError
        );
    }

    /**
     * 错误日志
     * @param {Object} PDOException $e
     * @param {Object} int $retryCount
     * @param {Object} int $maxRetries
     */
    protected function logDatabaseError(PDOException $e, int $retryCount, int $maxRetries)
    {
        // 记录详细错误信息
        $e = sprintf(
            "数据库操作失败 (尝试 %d/%d)\nSQL: %s\n详情:%s",
            $retryCount,
            $maxRetries,
            isset($e->queryString) ? $e->queryString : '未知',
            $e
        );

        error_log($e . PHP_EOL);

        // 可选：发送警报（对于生产环境）
        if ($retryCount === $maxRetries) {
            $this->sendAlert($e);
        }
    }

    /**
     * 发送提醒
     * @param {Object} $e
     */
    protected function sendAlert($e)
    {
        return false;
    }

    // ######################################  数据库 END  ######################################

}
