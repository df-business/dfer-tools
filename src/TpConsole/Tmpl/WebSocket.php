<?php
declare(strict_types = 1);
namespace Dfer\Tools\TpConsole\Tmpl;

use think\console\input\{Argument,Option};

use Workerman\Worker;
use Workerman\Lib\Timer;
use think\exception\ErrorException;

use Dfer\Tools\TpConsole\FileMonitor;

defined("HEARTBEAT_TIME")||define('HEARTBEAT_TIME', 55);
defined("MAX_REQUEST")||define('MAX_REQUEST', 1000);


/**
 * +----------------------------------------------------------------------
 * | WS后台服务
 * | composer require topthink/framework
 * | composer require workerman/workerman
 * |
 * | eg:
 * | php think ws
 * | php think ws -m d
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
class WebSocket extends WebSocketCommand
{

    private $count;
    protected function configure()
    {
        $this->setName('ws')
          ->addArgument('action', Argument::REQUIRED, "start|stop|restart|reload|status|connections", 'start')
          ->addOption('mode', 'm', Option::VALUE_OPTIONAL, '后台运行workerman服务')
          ->addOption('debug', 'd', Option::VALUE_REQUIRED, '调试模式。1:开启;0:关闭', true)
          ->addOption('port', 'p', Option::VALUE_REQUIRED, '监听端口', 99)
          ->addOption('count', 'c', Option::VALUE_REQUIRED, '开启的线程数', 3)
          ->setDescription('workerman脚本。输入`php think game -h`查看说明');
    }


    public function init()
    {
        global $argv;
        try {
            if ($this->debug) {
                new FileMonitor();
            }
            $port = $this->input->getOption('port');
            $this->host="websocket://0.0.0.0:{$port}";
            $this->count = $this->input->getOption('count');

            $argv = [];
            $action = $this->input->getArgument('action');
            $mode = $this->input->getOption('mode');
            array_unshift($argv, 'think', $action);
            if ($mode == 'd') {
                $argv[] = '-d';
            } elseif ($mode == 'g') {
                $argv[] = '-g';
            }

            $this->service();
        } catch (ErrorException $e) {
            $this->tpPrint(sprintf("\n%s\n\n%s %s", $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    public function service()
    {
        $this->worker = new Worker($this->host);
        $this->worker->name = 'WS服务';
        $this->worker->count = $this->count;
        $this->worker->list = array();

        $this->worker->onWorkerStart = function (Worker $worker) {
            $this->debugPrint("服务 {$worker->id} 已开启");
            $this->onWorkerStart($worker);
            $worker->onConnect    = array($this, 'onConnect');
            $worker->onMessage    = array($this, 'onMessage');

            if ($worker->id === 0) {
                // 间歇性检测客户端状态
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
