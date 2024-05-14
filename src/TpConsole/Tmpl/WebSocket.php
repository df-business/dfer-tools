<?php

namespace Dfer\Tools\TpConsole\Tmpl;

use think\console\input\{Argument, Option};
use think\exception\ErrorException;
use Workerman\Worker;
use Dfer\Tools\TpConsole\FileMonitor;
use Dfer\Tools\Statics\Common;

/**
 * +----------------------------------------------------------------------
 * | WS后台服务
 * | composer require topthink/framework
 * | composer require workerman/workerman
 * |
 * | eg:
 * | php think ws
 * | php think ws -m d
 * |
 * | 注意:
 * | windows操作系统下无法在一个php文件里初始化多个Worker
 * | https://www.workerman.net/doc/workerman/faq/multi-woker-for-windows.html
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
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status|connections", 'start')
            ->addOption('mode', 'm', Option::VALUE_OPTIONAL, '模式。d:后台运行;g:优雅地停止')
            ->addOption('debug', 'd', Option::VALUE_REQUIRED, '调试模式。1:开启;0:关闭', true)
            ->addOption('port', 'p', Option::VALUE_REQUIRED, '监听端口', 99)
            ->addOption('count', 'c', Option::VALUE_REQUIRED, '开启的线程数。windows操作系统下只支持1个线程', 3)
            ->setDescription('workerman脚本。输入`php think ws -h`查看说明');
    }


    public function init()
    {
        global $argv;
        try {
            $port = $this->input->getOption('port');
            // 应用层通信协议和侦听地址
            $this->host = "websocket://0.0.0.0:{$port}";
            $this->count = $this->input->getOption('count');
            $argv = [];
            $action = $this->input->getArgument('action');
            $mode = $this->input->getOption('mode');
            array_unshift($argv, 'think', $action);
            switch($mode){
                case 'd':
                    // 后台运行，关闭终端不受影响。eg:php think dfer:test -m d
                    $argv[] = '-d';
                    $this->debug=false;
                    break;
                case 'g':
                    // 优雅地停止。eg:php think dfer:test stop -m g
                    $argv[] = '-g';
                    break;
                default:
                    break;
            }

            if ($this->debug) {
                $root_log = Common::str("{0}/data/logs/{1}/ws",[Common::getRootPath(),date('Ym')]);
                $this->mkDirs($root_log);
                $time = $this->getTime(time());
                // 以守护进程方式(-d启动)运行时，获取终端输出
                Worker::$stdoutFile = "{$root_log}/stdout_{$time}.log";
                // workerman日志文件
                Worker::$logFile = "{$root_log}/workerman_{$time}.log";
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
        // 工作进程的数量。
        $this->worker->count = $this->count;
        // 客户端列表
        $this->worker->client_list = array();

        // 绑定自定义方法
        $this->worker->onWorkerStart = array($this, 'onWorkerStart');
        $this->worker->onWorkerReload = array($this, 'onWorkerReload');
        $this->worker->onWorkerStop = array($this, 'onWorkerStop');
        $this->worker->onWorkerExit = array($this, 'onWorkerExit');
        $this->worker->onConnect    = array($this, 'onConnect');
        $this->worker->onMessage    = array($this, 'onMessage');
        $this->worker->onClose    = array($this, 'onClose');
        $this->worker->onError    = array($this, 'onError');
        $this->worker->onBufferFull    = array($this, 'onBufferFull');
        $this->worker->onBufferDrain    = array($this, 'onBufferDrain');
        Worker::$onMasterReload    = array($this, 'onMasterReload');
        Worker::$onMasterStop    = array($this, 'onMasterStop');

        Worker::runAll();
    }
}
