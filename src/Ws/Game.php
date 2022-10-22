<?php
declare(strict_types = 1);

namespace Dfer\Tools\Ws;

use think\console\input\Argument;
use think\console\input\Option;

use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;

use Dfer\Tools\Ws\Modules\GameModel;
use Dfer\Tools\Ws\Modules\FileMonitor;
use Dfer\Tools\Ws\Modules\Common;

defined("HEARTBEAT_TIME")||define('HEARTBEAT_TIME', 55);
defined("MAX_REQUEST")||define('MAX_REQUEST', 1000);
    
class Game extends GameModel
{
    const HOST='websocket://0.0.0.0:99';
    const DEBUG=false;
  
    protected function configure()
    {
        $this->setName('game')
          ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status|connections", 'start')
          ->addOption('mode', 'm', Option::VALUE_OPTIONAL, 'Run the workerman server in daemon mode.')
          ->setDescription('...');
    }
    
        
    public function init()
    {
        global $db,$input,$output;
        try {
            if (DEBUG) {
                new FileMonitor();
            }
            $action = $input->getArgument('action');
            $mode = $input->getOption('mode');
            global $argv;
            $argv = [];
            array_unshift($argv, 'think', $action);
            if ($mode == 'd') {
                $argv[] = '-d';
            } elseif ($mode == 'g') {
                $argv[] = '-g';
            }
                        
            $this->ws_init();
        } catch (Exception $e) {
            $this->print($e->getMessage());
        }
    }
    
    public function ws_init()
    {
        global $ws_worker,$global_uid;
        $global_uid = 0;
        $ws_worker = new Worker(self::HOST);
        $ws_worker->name = '游戏后台';
        $ws_worker->count = 6;
        $ws_worker->list = array();        
        $ws_worker->onWorkerStart = function (Worker $worker) {
            $this->debug_print("服务 {$worker->id} 开启...");
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
