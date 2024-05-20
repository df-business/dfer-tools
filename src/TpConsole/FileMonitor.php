<?php
namespace Dfer\Tools\TpConsole;

use Dfer\Tools\Common;
use Workerman\Worker;
use Workerman\Lib\Timer;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * +----------------------------------------------------------------------
 * | workerman文件监控组件
 * |
 * | https://www.workerman.net/doc/workerman/components/file-monitor.html
 * | 只有在debug模式下才生效，daemon下不会执行文件监控（为何不支持daemon模式见下面说明）。
 * | 只有在Worker::runAll运行后加载的文件才能热更新，或者说只有在onXxx{...}回调(比如:onWorkerStart)中载入的文件(比如:require_once)平滑重启后才会自动更新，启动脚本中直接载入的文件或者写死的代码运行reload不会自动更新
 * |
 * | 通过向主进程发送信号来触发主进程的relaod
 * | https://www.workerman.net/doc/workerman/faq/reload-principle.html
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
class FileMonitor extends Common
{
    private $last_mtime;

    /**
     * @param {Object} $is_cur_dir 是否检查当前脚本所在的目录
     */
    public function __construct($is_cur_dir = false)
    {
        if ($is_cur_dir) {
            // 检查当前目录
            $monitor_dir = realpath(__DIR__);
        } else {
            // 检查指定目录
            if (defined('ROOT_PATH')) {
                $root = ROOT_PATH;
                $monitor_dir = $root . "/application/api/command/";
            } else {
                // >=tp6
                $root = app()->getRootPath();
                $monitor_dir = $root . "/app/command/";
            }
        }

        $this->last_mtime = time();

        $worker = new Worker();
        $worker->name = '文件监视器';
        $worker->reloadable = false;
        $worker->onWorkerStart = function () use ($monitor_dir) {
            // 仅在守护程序模式下监视文件
            if (!Worker::$daemonize) {
                // 每秒检查文件的时间
                Timer::add(1, [$this, 'checkFilesChange'], array($monitor_dir));
            }
        };
    }

    /**
     * 检查目录里的文件变化
     * @param {Object} $monitor_dir 检测目录
     */
    public function checkFilesChange($monitor_dir)
    {
        // 递归遍历目录
        $dir_iterator = new RecursiveDirectoryIterator($monitor_dir);
        $iterator = new RecursiveIteratorIterator($dir_iterator);
        foreach ($iterator as $file) {
            // 仅检查php文件
            if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) != 'php') {
                continue;
            }
            // 排除自身(FileMonitor.php)
            if (in_array($file->getBasename('.php'), [$this->getLastSlashStr(get_class())])) {
                continue;
            }
            // 检查时间
            if ($this->last_mtime < $file->getMTime()) {

                echo PHP_EOL;
                echo "{$file} 已更新";
                echo PHP_EOL;

                // 向主进程发送SIGUSR1信号以进行重新加载
                posix_kill(posix_getppid(), SIGUSR1);
                $this->last_mtime = $file->getMTime();
                break;
            }
        }
    }
}
