<?php
declare(strict_types = 1);

namespace Dfer\Tools\Console;

use think\console\input\Argument;
use think\console\input\Option;

use Workerman\Worker;
use Workerman\Lib\Timer;

use Dfer\Tools\Console\Modules\GameModelTmpl;
use Dfer\Tools\Console\Modules\FileMonitor;

defined("HEARTBEAT_TIME")||define('HEARTBEAT_TIME', 55);
defined("MAX_REQUEST")||define('MAX_REQUEST', 1000);


/**
 * +----------------------------------------------------------------------
 * | 游戏后台服务
 * | composer require topthink/framework
 * | composer require workerman/workerman
 * |
 * | eg:
 * | php think game
 * | php think game -m d
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
class Game extends GameModelTmpl
{
    const DEBUG=true;
    const HOST='websocket://0.0.0.0:99';
  
    protected function configure()
    {
        $this->setName('game')
          ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status|connections", 'start')
          ->addOption('mode', 'm', Option::VALUE_OPTIONAL, '后台运行workerman服务')
          ->addOption('debug', 'd', Option::VALUE_OPTIONAL, '调试模式。1:开启;0:关闭', self::DEBUG)
          ->setDescription('workerman脚本。输入`php think game -h`查看说明');
    }
    
        
    public function init()
    {
        global $input,$argv;
        try {
            if (self::$debug) {
                new FileMonitor();
            }
            $argv = [];
            $action = $input->getArgument('action');
            $mode = $input->getOption('mode');
            array_unshift($argv, 'think', $action);
            if ($mode == 'd') {
                $argv[] = '-d';
            } elseif ($mode == 'g') {
                $argv[] = '-g';
            }
                        
            $this->wsInit();
        } catch (\think\exception\ErrorException $e) {
            self::$common_base->tpPrint(sprintf("\n%s\n\n%s %s", $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
    
    public function wsInit()
    {
        global $ws_worker,$global_uid;
        $global_uid = 0;
        $ws_worker = new Worker(self::HOST);
        $ws_worker->name = '游戏后台';
        $ws_worker->count = 6;
        $ws_worker->list = array();
        $ws_worker->onWorkerStart = function (Worker $worker) {
            self::$common_base->debugPrint("服务 {$worker->id} 开启...");
            $this->onWorkerStart($worker);
            $worker->onConnect    = array($this, 'onConnect');
            $worker->onMessage    = array($this, 'onMessage');
                                                
            if ($worker->id === 0) {
                // pingpong
                Timer::add(10, function () use ($worker) {
                    $time_now = time();
                    foreach ($worker->connections as $connection) {
                        if (empty($connection->lastMessageTime)) {
                            $connection->lastMessageTime = $time_now;
                            continue;
                        }
                        if ($time_now - $connection->lastMessageTime > HEARTBEAT_TIME) {
                            $connection->close();
                        }
                    }
                });
            }
        };
                
        Worker::runAll();
    }
}