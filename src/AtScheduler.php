<?php

/**
 * +----------------------------------------------------------------------
 * | 定时任务（依赖于linux的at命令）
 * | at 命令用于安排一次性任务在指定时间执行，命令执行完之后会自动删除任务
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

class AtScheduler extends Common
{
    private $debug = true;

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
    public function debugAtScheduler()
    {
        if ($this->debug)
            parent::debugAtScheduler(func_get_args());
    }

    /**
     * 添加定时任务
     * @param string $command 要执行的命令
     * @param string $time 时间字符串（支持多种格式）。如：2026-03-05 10:30:00、14:30 2026-01-15、+1 hour
     * @return array ['success'=>bool, 'job_id'=>int, 'output'=>string]
     */
    public function addJob($command, $time)
    {
        try {
            $this->debugAtScheduler($command, $time);
            $validTime = $this->normalizeTime($time);
            // var_dump($validTime);
            if ($validTime === false) {
                throw new DferException('时间格式不正确', 100, $time);
            }

            $noAccess = $this->sudoNoAccess($command);
            if ($noAccess === true) {
                throw new DferException('需要给www提权：sudo nano /etc/sudoers => www ALL=(ALL) NOPASSWD: ALL', 101, $command);
            }

            // 构建at命令。需要以root执行at（默认at是以www权限执行，会导致权限不够而无法正常执行命令)
            $at_cmd = sprintf(
                'echo "%s" | sudo /usr/bin/at "%s" 2>&1',
                escapeshellcmd($command),
                $validTime
            );
            // var_dump($at_cmd);

            // 执行命令
            $output = [];
            $return_var = 0;
            exec($at_cmd, $output, $return_var);
            if ($return_var !== 0) {
                throw new DferException('exec执行失败', 102, [$at_cmd, $output, $return_var]);
            }

            $full_output = implode("\n", $output);

            // 解析任务编号
            $job_id = $this->getJobId($full_output);

            if ($job_id === false) {
                throw new DferException('at执行失败', 103, [$full_output, $job_id]);
            }

            $result = [
                'code' => 0,
                'job_id' => $job_id,
                'output' => $full_output,
                'command' => $command,
                'scheduled_time' => $validTime
            ];

            $this->debugAtScheduler($at_cmd, $result);

            return $result;
        } catch (DferException $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            $data = $e->getErrorData();

            $result = [
                'code' => $code,
                'error' => $message,
                'output' => $data
            ];

            $this->debugAtScheduler($result);

            return $result;
        }
    }

    /**
     * 删除定时任务
     * @param int $job_id 任务编号
     * @return array ['success'=>bool, 'output'=>string]
     */
    public function deleteJob($job_id)
    {
        if (!is_numeric($job_id)) {
            return ['success' => false, 'output' => 'Invalid job ID'];
        }

        exec("atrm " . escapeshellarg($job_id) . " 2>&1", $output, $return_var);
        $full_output = implode("\n", $output);

        $result = [
            'success' => $return_var === 0,
            'output' => $full_output
        ];

        $this->debugAtScheduler($job_id, $result);

        return $result;
    }

    /**
     * 列出所有待执行任务
     * at -l
     * at -c 54
     * sudo journalctl -u atd
     * @return array
     */
    public function listJobs()
    {
        exec('atq 2>&1', $output, $return_var);

        $jobs = [];
        foreach ($output as $line) {
            if (preg_match('/^(\d+)\s+(\S+\s+\S+)\s+(\S+)\s+(.+)$/', $line, $matches)) {
                $jobs[] = [
                    'job_id' => $matches[1],
                    'date' => $matches[2],
                    'time' => $matches[3],
                    'queue' => $matches[4]
                ];
            }
        }

        return [
            'success' => true,
            'jobs' => $jobs,
            'output' => $output
        ];
    }

    /**
     * 标准化时间格式为 at 命令接受的格式
     * @param mixed $time 时间字符串或时间戳
     * @return string 标准化后的时间字符串
     */
    private function normalizeTime($time)
    {
        // 时间戳
        if (is_numeric($time)) {
            return date('H:i Y-m-d', $time);
        }
        // 时间字符串
        $timestamp = strtotime($time);
        if ($timestamp) {
            return date('H:i Y-m-d', $timestamp);
        }
        return false;
    }

    /**
     * 运行sudo命令时，判断命令中的sudo是否能正常运行
     * @return array 执行结果 true 无法运行sudo false 可以运行sudo
     */
    function sudoNoAccess()
    {
        $result = false;

        $cmd = "sudo /usr/bin/php";
        exec($cmd, $output, $return_var);
        // var_dump($output,$return_var);
        if ($return_var === 0) {
            // echo "命令执行成功";
        } else {
            // echo "命令执行失败，错误码: $return_var";
            $result = true;
        }

        return $result;
    }

    /**
     * 解析at命令输出中的任务编号
     * @param string $output
     * @return int|false
     */
    private function getJobId($output)
    {
        // 尝试匹配 "job 12 at 2024-01-15 10:30"
        if (preg_match('/job\s+(\d+)\s+at/', $output, $matches)) {
            return (int)$matches[1];
        }
        return false;
    }
}
