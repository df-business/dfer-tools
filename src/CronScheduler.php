<?php

/**
 * +----------------------------------------------------------------------
 * | 周期性任务（依赖于linux的crontab命令）
 * | 等同于通过`crontab -e`编辑任务
 * | 如：
 * |    $result = AtScheduler::addJob('/usr/bin/php /www/wwwroot/debug.ydt.tye3.com/think ydt:overdue 7B227469746C65223A22E58F8CE799BE222C2274696D65223A22323032362D312D3135222C226D6F62696C65223A223138383037323034393535222C2274656D706C6174655F6B6579223A226A7373715F7478227D', $param);
 * |    $result = AtScheduler::deleteJob($param);
 * |    $result = AtScheduler::listJobs();
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

use DOMDocument, Closure, Exception, Error, Throwable, DateTime, stdClass;

class CronScheduler extends Common
{
    private $cronFile;
    private $debug = true;

    public function __construct($config = [])
    {
        // 为每个PHP进程创建独立的cron文件
        $this->cronFile = '/tmp/cron_' . getmypid() . '.txt';
        if($config)
            $this->setConfig($config);
    }

    public function __destruct()
    {
        // 清理临时文件
        if (file_exists($this->cronFile)) {
            unlink($this->cronFile);
        }
    }

    /**
     * 设置默认参数
     * @param Array $config
     */
    public function setConfig($config)
    {
        $this->debug = $config['debug'] ?? $this->debug;
        return $this;
    }

    /**
     * 重写父级方法
     */
    public function debugCronScheduler()
    {
        if ($this->debug)
            parent::debugCronScheduler(func_get_args());
    }

    /**
     * 添加一次性cron任务（使用时间戳）
     * @param string $command 命令
     * @param Object $time 时间（支持多种格式）。如：1768320000、2026-03-05 10:30:00、14:30 2026-01-15、+1 hour
     * @return string 任务标识符（用于删除）
     */
    public function addJob($command, $time)
    {
        $this->debugCronScheduler($command, $time);
        $validTime = $this->normalizeTime($time);
        // var_dump($validTime);
        if ($validTime === false) {
            return [
                'success' => false,
                'error' => '时间格式不正确',
                'input_time' => $time
            ];
        }

        // 生成唯一ID
        $jobId = 'JOB_' . uniqid() . '_' . md5($command . $validTime);

        // 创建cron行
        $minute = date('i', $validTime);
        $hour = date('H', $validTime);
        $day = date('j', $validTime);
        $month = date('n', $validTime);
        $weekday = date('w', $validTime); // 0-6, 0=Sunday

        $cronLine = "$minute $hour $day $month $weekday $command # $jobId";

        // 获取当前crontab
        exec('crontab -l 2>/dev/null', $output, $return_var);

        // 添加新行
        $crontab = implode(PHP_EOL, $output) . PHP_EOL . $cronLine . PHP_EOL;

        // 写入临时文件
        file_put_contents($this->cronFile, $crontab);

        // 安装新的crontab。将指定文件的内容安装为当前用户的crontab，修改之后立即生效，cron守护进程（crond）会自动检测文件变化
        exec('crontab ' . $this->cronFile, $output, $return_var);

        $this->debugCronScheduler('crontab ' . $this->cronFile, $output, $return_var,$jobId);

        return $jobId;
    }

    /**
     * 删除任务
     * @param string $jobId 任务标识符
     * @return bool
     */
    public function deleteJob($jobId)
    {
        // 获取当前crontab
        exec('crontab -l 2>/dev/null', $output, $return_var);

        // 过滤掉包含该jobId的行
        $newCrontab = [];
        foreach ($output as $line) {
            if (strpos($line, $jobId) === false) {
                $newCrontab[] = $line;
            }
        }
        // 写入临时文件
        file_put_contents($this->cronFile, implode(PHP_EOL, $newCrontab) . PHP_EOL);

        // 安装新的crontab
        exec('crontab ' . $this->cronFile, $output, $return_var);

        $this->debugCronScheduler('crontab ' . $this->cronFile, $output, $return_var,$jobId);

        return $return_var === 0;
    }

    /**
     * 列出所有cron任务
     * @return array 任务列表
     */
    public function listJobs()
    {
        exec('crontab -l 2>/dev/null', $output, $return_var);

        $jobs = [];
        foreach ($output as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // 解析cron行
            if (preg_match('/^([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+(.+?)(?:\s+#\s+(.+))?$/', $line, $matches)) {
                $jobId = $matches[7] ?? 'N/A';
                $jobs[] = [
                    'job_id' => $jobId,
                    'schedule' => $matches[1] . ' ' . $matches[2] . ' ' . $matches[3] . ' ' . $matches[4] . ' ' . $matches[5],
                    'command' => $matches[6],
                    'raw_line' => $line
                ];
            }
        }

        return $jobs;
    }

    /**
     * 标准化时间格式为命令接受的格式
     * @param mixed $time 时间字符串或时间戳
     * @return string 标准化后的时间戳
     */
    private function normalizeTime($time)
    {
        // 时间戳
        if (is_numeric($time)) {
            return $time;
        }
        // 时间字符串
        $timestamp = strtotime($time);
        if ($timestamp) {
            return $timestamp;
        }
        return false;
    }
}
