<?php

namespace Dfer\Tools\TpConsole\Tmpl;

use Workerman\Worker;
use Workerman\Lib\Timer;
use Workerman\Connection\TcpConnection;
use Dfer\Tools\TpConsole\Command;
use Dfer\Tools\Statics\Common;
use Channel\Client as ChannelClient;
use GlobalData\Client as GlobalDataClient;

/**
 * +----------------------------------------------------------------------
 * | workerman的console类模板
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
class WebSocketCommand extends Command
{
    // 应用层通信协议和侦听地址。例如：websocket://0.0.0.0:99
    protected $host;
    // 工作进程数量
    protected $count;
    // 当前进程的worker对象。会根据所处进程发生变化，等同于onXXX回调里返回的worker对象。
    protected $worker;
    // Channel服务端
    // https://www.workerman.net/doc/workerman/components/channel.html
    protected $channel_server;
    // GlobalData服务端
    // https://www.workerman.net/doc/workerman/components/global-data.html
    protected $global_data_server;
    // GlobalData客户端
    protected $global_data;
    // 超时时间(秒)。客户端需要每隔`heartbeat_time`秒至少发送一次数据(`↑`字符)给服务端，不然服务器会自动断开连接
    // https://www.workerman.net/doc/workerman/faq/heartbeat.html
    protected $heartbeat_time = 55;
    // 判定客户端存活状态的定时器运行间隔(秒)
    protected $timer_interval = 5;
    // 客户端列表。每个进程里的数据是独立的
    protected $client_list = array();
    // 组列表。用来分组发送消息
    protected $group_list;


    ////////////////////////////////////////////////// Worker START //////////////////////////////////////////////////

    /**
     * 工作进程启动时发出。
     * @param {Object} Worker $worker
     */
    public function onWorkerStart(Worker $worker)
    {
        $this->debugPrint("[工作进程 {$worker->id}] 已开启");

        // ********************** 拓展服务 START **********************
        ChannelClient::connect();
        $this->global_data = new GlobalDataClient('127.0.0.1:2207');
        if(!isset($this->global_data->client_list)){
            $this->global_data->client_list=[];
        }

        // **********************  拓展服务 END  **********************

        // ********************** 订阅事件 START **********************
        // 单发。向所有连接到Channel服务的工作进程内的单个客户端连接发送数据
        ChannelClient::on($worker->id, function ($event_data) use ($worker) {
            $to_connection_id = $event_data['connection_id'];
            $message = $event_data['content'];
            if (!isset($worker->connections[$to_connection_id])) {
                return $this->debugPrint("[工作进程 {$worker->id}][连接 {$to_connection_id}] 不存在");
            }
            $to_connection = $worker->connections[$to_connection_id];
            $to_connection->send($message);
        });
        // 广播。向所有连接到Channel服务的工作进程内的所有客户端连接发送数据
        ChannelClient::on("broadcast", function ($event_data) use ($worker) {
            $message = $event_data['content'];
            foreach ($worker->connections as $connection) {
                $connection->send($message);
            }
        });
        // 单组群发。向所有连接到Channel服务的工作进程内的单个组内的所有客户端连接发送数据
        ChannelClient::on('send_to_group', function ($event_data) {
            $group_id = $event_data['group_id'];
            $content = $event_data['content'];
            if (isset($this->group_list[$group_id])) {
                foreach ($this->group_list[$group_id] as $connection) {
                    $connection->send($content);
                }
            }
        });

        /**
         * 队列。
         * 同一工作进程内依次执行程序（执行期间会堵塞该工作进程，无法进行其余操作，包括建立连接、发布事件，直到所有队列执行完毕），不同工作进程互不影响
         * 多进程模式下，发布事件会被随机分配到任一闲置工作进程，都被占用则排队等待，直到有进程闲置
         */
        ChannelClient::watch('add_task', function($event_data) use ($worker) {
            $this->debugPrint("[队列编号 {$event_data}][工作进程 {$worker->id}] 正在执行 ".Common::getTime(),37,40);
            sleep(9);
            $this->debugPrint("[队列编号 {$event_data}][工作进程 {$worker->id}] 执行完成 ".Common::getTime(),37,40);
        });
        // **********************  订阅事件 END  **********************

        // ********************** 判定客户端存活状态 START **********************
        // 由客户端主动发送数据(`↑`字符)来刷新`lastMessageTime`，一旦超过`heartbeat_time`，则判定客户端意外断开连接，服务端会关闭该连接
        Timer::add($this->timer_interval, function () use ($worker) {
            $time_now = time();
            foreach ($worker->connections as $connection) {
                if (empty($connection->lastMessageTime)) {
                    $connection->lastMessageTime = $time_now;
                    continue;
                }
                if ($time_now - $connection->lastMessageTime > $this->heartbeat_time) {
                    $this->debugPrint("[工作进程 {$connection->worker->id}][连接 {$connection->id}] 失去响应");
                    $connection->close();
                }
            }
        });
        // **********************  判定客户端存活状态 END  **********************
    }

    /**
     * 当工作进程获得重新加载信号时发出。
     * @param {Object} Worker $worker
     */
    public function onWorkerReload(Worker $worker)
    {
        $this->debugPrint("[工作进程 {$worker->id}] 已重载");
        foreach ($worker->connections as $connection) {
            $this->send($connection, $this->success(null, '[工作进程 {$worker->id}][连接 {$connection->id}] 已重载'));
        }
    }

    /**
     * 工作进程停止时发出。
     * @param {Object} Worker $worker
     */
    public function onWorkerStop(Worker $worker)
    {
        $this->debugPrint("[工作进程 {$worker->id}] 已停止");
    }

    /**
     * 当工作进程退出时发出。
     * @param {Object} Worker $worker
     */
    public function onWorkerExit(Worker $worker, $status, $pid)
    {
        $this->debugPrint("[工作进程 {$worker->id}] 已退出");
        foreach ($worker->connections as $connection) {
            $this->send($connection, $this->fail('[工作进程 {$worker->id}][连接 {$connection->id}] 已退出'));
        }
    }

    /**
     * 当主进程获得重新加载信号时发出。
     */
    public function onMasterReload()
    {
        $this->debugPrint("[主进程 {$this->worker->id}] 已重载");
    }

    /**
     * 当主进程终止时发出。
     */
    public function onMasterStop()
    {
        $this->debugPrint("[主进程 {$this->worker->id}] 已停止");
    }

    /**
     * 在成功建立套接字连接时发出。
     * 多个[工作进程]的情况下，加入的[工作进程]是随机分配的
     * @param {Object} TcpConnection $connection
     */
    public function onConnect(TcpConnection $connection)
    {
        $this->debugPrint("[工作进程 {$connection->worker->id}][连接 {$connection->id}] 已加入");

        $connection->headers = [
            // 验证协议
            // 'Sec-WebSocket-Protocol: dfer.top',
        ];

        // https://www.workerman.net/doc/workerman/appendices/about-websocket.html
        $connection->onWebSocketConnect = function ($connection, $buffer) {
            // 网页调用
            if (isset($_SERVER['HTTP_SEC_WEBSOCKET_PROTOCOL'])) {
                $protocols         = explode(',', $_SERVER['HTTP_SEC_WEBSOCKET_PROTOCOL']);
                $params            = json_decode(urldecode($protocols[1]));
                $src               = explode('/', $_SERVER['REQUEST_URI'])[1];
                $src               = explode('?', $src)[0];
                $get               = $_GET;
                $connection->token = $params->token ?? '';
            }
            return $this->connectLogic($connection);
        };
        $connection->onWebSocketClose = function ($connection) {
        };
        $connection->onWebSocketPing = function ($connection, $ping_data) {
        };
        $connection->onWebSocketPong = function ($connection, $pong_data) {
        };
        $connection->onError = function ($connection, $status, $message) {
        };
        $connection->onBufferFull = function ($connection) {
        };
    }
    //////////////////////////////////////////////////  Worker END  //////////////////////////////////////////////////

    ////////////////////////////////////////////////// TcpConnection START //////////////////////////////////////////////////

    /**
     * 接收数据时发出。
     * @param {Object} TcpConnection $connection
     * @param {Object}               $data
     */
    public function onMessage(TcpConnection $connection, $data)
    {
        // 更新lastMessageTime
        $connection->lastMessageTime = time();

        // 客户端使用`↑`来刷新`lastMessageTime`，服务端会返回`↓`，不再进行后续操作
        if ($data == '↑') {
            return $this->send($connection, '↓');
        }

        return $this->messageLogic($connection, $data);
    }

    /**
     * 当套接字的另一端发送FIN数据包时发出。
     * @param {Object} TcpConnection $connection
     */
    public function onClose(TcpConnection $connection)
    {
        $this->debugPrint("[工作进程 {$connection->worker->id}][连接 {$connection->id}] 已断开");
        return $this->closeLogic($connection);
    }

    /**
     * 当连接发生错误时发出。
     * @param {Object} TcpConnection $connection
     * @param {Object}               $code
     * @param {Object}               $msg
     */
    public function onError(TcpConnection $connection, $code, $msg)
    {
        $this->debugPrint("[工作进程 {$connection->worker->id}][连接 {$connection->id}] {$code} {$msg}");
    }

    /**
     * 当发送缓冲区变空时发出。
     * @param {Object} TcpConnection $connection
     */
    public function onBufferDrain(TcpConnection $connection)
    {
        $this->debugPrint("[工作进程 {$connection->worker->id}][连接 {$connection->id}] 发送缓冲区数据已发送完毕");
    }

    /**
     * 当发送缓冲区已满时发出。
     * @param {Object} TcpConnection $connection
     */
    public function onBufferFull(TcpConnection $connection)
    {
        $this->debugPrint("[工作进程 {$connection->worker->id}][连接 {$connection->id}] 发送缓冲区数据已满");
    }

    //////////////////////////////////////////////////  TcpConnection END  //////////////////////////////////////////////////

    ////////////////////////////////////////////////// 自定义方法 START //////////////////////////////////////////////////

    /**
     * 在连接上发送数据。
     * 只能对同一进程内的连接发送数据。
     * @param TcpConnection $connection
     * @param Object        $send_buffer 发送字符串
     * @param Bool          $raw         设置是否发送原始数据
     **/
    public function send(TcpConnection $connection, $send_buffer, $raw = false)
    {
        $connection->send($send_buffer, $raw);
    }

    /**
     * 通过Channel服务在不同工作进程的连接上发送数据
     * @param {Object} $content 内容
     * @param {Object} $target 发送目标
     * @param {Object} $is_group 分组群发
     **/
    public function cSend($content, $target = null, $is_group = false)
    {
        if ($is_group) {
            ChannelClient::publish('send_to_group', array(
                'group_id' => $target,
                'content' => $this->success($content)
            ));
        } else {
            if ($target) {
                // 客户端id。采用"工作进程id-连接id"，比如：1-3
                $client_id = explode('-', $target);
                if (count($client_id) == 2) {
                    [$to_worker_id, $to_connection_id] = $client_id;
                    ChannelClient::publish($to_worker_id, array(
                        'connection_id' => $to_connection_id,
                        'content' => $this->success($content)
                    ));
                } else {
                    $this->debugPrint("[Channel服务][发布事件 {$target}] 出错");
                }
            } else {
                ChannelClient::publish("broadcast", array(
                    'content' => $this->success($content)
                ));
            }
        }
    }

    /**
     * 获取状态。
     * @param TcpConnection $connection
     * @param Bool          $raw_output 设置是否获取原始数据
     **/
    public function getStatus(TcpConnection $connection, $raw_output = false)
    {
        $connection->getStatus($raw_output);
    }

    /**
     * 关闭连接。
     * @param TcpConnection $connection
     * @param Object        $data       返回信息
     * @param Bool          $raw        设置是否获取原始数据
     **/
    public function close(TcpConnection $connection, $data = null, $raw = false)
    {
        $connection->close($data, $raw);
    }

    /**
     * 连接成功之后的业务逻辑
     * `connections`里的`key`等同于`$connection->id`,各个[工作进程]里的`id`是相互独立的，初始`id`是1，断开连接不会销毁对应的`id`，每个新的连接都会在[工作进程]的最大`id`上累加，直到重启服务
     * 客户端数据可以用`$connection`来存储、读取，比如：`$connection->user_id=123;echo $connection->user_id;`
     * @param TcpConnection $connection
     */
    public function connectLogic(TcpConnection $connection)
    {
        $client_id = "{$connection->worker->id}-{$connection->id}";
        $connection->client_id = $client_id;

        $client_data = [
            'name' => "游客 {$client_id}"
        ];
        // 当前进程的所有连接
        $this->client_list[$client_id] = $client_data;
        // 所有进程的所有连接
        $this->global_data->client_list =array_merge($this->global_data->client_list,[$client_id=>$client_data]);

        return $this->cSend("[客户端 {$client_id}] 已加入");
    }

    /**
     * 当连接发送消息过来时
     * @param TcpConnection $connection
     * @param {Object}      $data
     */
    public function messageLogic(TcpConnection $connection, $data)
    {
        $dataJson = json_decode($data, true);
        if (!$dataJson || !is_array($dataJson)) {
            return $this->send($connection, $this->fail($data, '数据格式不正确'));
        }
        if (!$this->require($connection, $dataJson, 'cmd')) : return;endif;

        $client_id = $connection->client_id;

        $cmd = $dataJson['cmd'];
        switch ($cmd) {
            case "join_group":
                // 加入组。一个连接可以同时加入多个组
                if (!$this->require($connection, $dataJson, 'group_id')) : return;endif;
                $group_id = $dataJson['group_id'];
                $this->group_list[$group_id][$connection->id] = $connection;
                $connection->group_ids = $connection->group_id ?? array();
                $connection->group_ids[$group_id] = $group_id;
                return $this->send($connection, $this->success($dataJson, "[组 {$group_id}][客户端 {$client_id}] 已加入"));
                break;
            case "send_msg":
                // 跨进程发消息给单个用户、组或者所有人
                if (!$this->require($connection, $dataJson, ['content'])) : return;
                endif;
                if(isset($dataJson['group_id']))
                    $this->cSend($dataJson['content'], $dataJson['group_id'], true);
                else
                    $this->cSend($dataJson['content'], $dataJson['client_id']??null);
                return $this->send($connection, $this->success($dataJson));
                break;
            case "add_task":
                // 添加队列
                ChannelClient::enqueue('add_task', '测试 '.time());
                return $this->send($connection, $this->success($dataJson));
                break;
            default:
                return $this->send($connection, $this->fail($dataJson));
                break;
        }
    }

    /**
     * 当连接断开时，广播给所有连接
     * @param TcpConnection $connection
     */
    public function closeLogic(TcpConnection $connection)
    {
        $client_id = $connection->client_id;

        // 清理客户端列表
        unset($this->client_list[$client_id]);
        $client_list=$this->global_data->client_list;
        unset($client_list[$client_id]);
        $this->global_data->client_list=$client_list;

        return $this->cSend("[客户端 {$client_id}] 已退出");
    }


    /**
     * 日志初始化
     * @param {Object} $var 变量
     **/
    public function logInit($var = null)
    {
        if ($this->debug) {
            $root_log = Common::str("{0}/data/logs/{1}/ws", [Common::getRootPath(), date('Ym')]);
            Common::mkDirs($root_log);
            $time = Common::getTime(null, 'd');
            // 以守护进程方式(-d启动)运行时，获取终端输出
            Worker::$stdoutFile = "{$root_log}/stdout_{$time}.log";
            // workerman日志文件
            Worker::$logFile = "{$root_log}/workerman_{$time}.log";
        }
    }

    /**
     * 必填项
     * @param {Object} $data
     * @param {Object} $msg 信息
     */
    public function require(TcpConnection $connection, $data, $item)
    {
        $items = is_array($item) ? $item : [$item];
        foreach ($items as $key => $value) {
            if (!isset($data[$value])) {
                $this->send($connection, $this->fail($data, "缺少参数[{$value}]"));
                return false;
            }
        }

        return true;
    }

    //////////////////////////////////////////////////  自定义方法 END  //////////////////////////////////////////////////
}
