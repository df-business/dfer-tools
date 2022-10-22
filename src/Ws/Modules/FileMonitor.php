<?php
declare(strict_types = 1);
namespace Dfer\Tools\Ws\Modules;

use Workerman\Worker;
use Workerman\Lib\Timer;

/**
 * 文件监控组件
 * https://www.workerman.net/doc/workerman/components/file-monitor.html
 **/
class FileMonitor
{
    public function __construct()
    {
        global $last_mtime;
        // 检查当前目录
        $monitor_dir = realpath(__DIR__);
        
        // worker
        $worker = new Worker();
        $worker->name = 'FileMonitor';
        $worker->reloadable = false;
        $last_mtime = time();
        
        $worker->onWorkerStart = function () use ($monitor_dir) {
            //     global $monitor_dir;
            // watch files only in daemon mode
            if (!Worker::$daemonize) {
                // chek mtime of files per second
                Timer::add(1, [$this,'check_files_change'], array($monitor_dir));
            }
        };
    }
    

    // check files func
    public function check_files_change($monitor_dir)
    {
        global $last_mtime;
        // recursive traversal directory
        $dir_iterator = new \RecursiveDirectoryIterator($monitor_dir);
        $iterator = new \RecursiveIteratorIterator($dir_iterator);
        foreach ($iterator as $file) {
            // var_dump($file->getBasename('.php'), [get_class(),Common::last_slash_str(get_class())]);
            // only check php files
            if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) != 'php') {
                continue;
            }
            // 排除部分文件
            if (in_array($file->getBasename('.php'), [Common::last_slash_str(get_class())])) {
                continue;
            }
                        
            // var_dump($last_mtime, $file->getMTime());
            // check mtime
            if ($last_mtime < $file->getMTime()) {
                echo $file." 已更新\n";
                // send SIGUSR1 signal to master process for reload
                posix_kill(posix_getppid(), SIGUSR1);
                $last_mtime = $file->getMTime();
                break;
            }
        }
    }
}
