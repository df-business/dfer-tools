<?php
declare(strict_types = 1);
namespace Dfer\Tools\Console\Modules;

use Workerman\Worker;
use Workerman\Lib\Timer;

/**
 * +----------------------------------------------------------------------
 * | workerman文件监控组件
 * |
 * | https://www.workerman.net/doc/workerman/components/file-monitor.html
 * | 只有在debug模式下才生效，daemon下不会执行文件监控（为何不支持daemon模式见下面说明）。
 * | 只有在Worker::runAll运行后加载的文件才能热更新，或者说只有在onXXX回调中加载的文件才能热更新。
 * | 如果开发者确实需要daemon模式开启文件监控及自动更新，可以自行更改代码，将Worker::$daemonize部分的判断去掉即可。
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
 *                 ......    .!%$! ..        | AUTHOR: dfer                             
 *         ......        .;o*%*!  .          | EMAIL: df_business@qq.com                             
 *                .:;;o&***o;.   .           | QQ: 3504725309                             
 *        .;;!o&****&&o;:.    ..        
 * +----------------------------------------------------------------------
 *
 */
class FileMonitor
{
    public function __construct()
    {
        global $last_mtime;
        
        // 检查当前目录
        // $monitor_dir = realpath(__DIR__);
        
        // 检查'/app/command'
        
        if(defined('ROOT_PATH')){   
         // tp5
         $root=ROOT_PATH;
         $monitor_dir = $root."/application/api/command/";
        }else{
         // tp6
         $root=app()->getRootPath();
         $monitor_dir = $root."/app/command/";
        }
        
        
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
            // var_dump($file->getBasename('.php'), [get_class(),Common::lastSlashStr(get_class())]);
            // only check php files
            if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) != 'php') {
                continue;
            }
            // 排除部分文件
            if (in_array($file->getBasename('.php'), [CommonBase::lastSlashStr(get_class())])) {
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
