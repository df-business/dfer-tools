<?php

namespace Dfer\Tools\TpConsole\Tmpl;

use Workerman\Worker;
use Workerman\Lib\Timer;
use Workerman\Connection\TcpConnection;
use Dfer\Tools\TpConsole\Command;
use Dfer\Tools\Statics\Common;

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
    // 监听地址
    protected $host;
    // 主worker对象
    protected $worker;
    // 超时时间(秒)。客户端需要每隔`heartbeat_time`秒至少发送一次数据(`↑`字符)给服务端，不然服务器会自动断开连接
    protected $heartbeat_time = 6000;
    // 判定客户端存活状态的定时器运行间隔(秒)
    protected $timer_interval = 10;

    ////////////////////////////////////////////////// Worker START //////////////////////////////////////////////////

    /**
     * 工作进程启动时发出。
     * @param {Object} Worker $worker
     */
    public function onWorkerStart(Worker $worker)
    {
        $this->debugPrint("[工作进程 {$worker->id}] 已开启");

        // 判定客户端存活状态。由客户端主动发送数据(`↑`字符)来刷新`lastMessageTime`，一旦超过`heartbeat_time`，则判定客户端意外断开连接，服务端会关闭该连接
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
    }

    /**
     * 当工作进程获得重新加载信号时发出。
     * @param {Object} Worker $worker
     */
    public function onWorkerReload(Worker $worker)
    {
        $this->debugPrint("[工作进程 {$worker->id}] 已重载");
        foreach ($worker->connections as $connection) {
            $this->send($connection,$this->success(null,'[工作进程 {$worker->id}][连接 {$connection->id}] 已重载'));
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
            $this->send($connection,$this->fail('[工作进程 {$worker->id}][连接 {$connection->id}] 已退出'));
        }
    }

    /**
     * 当主进程获得重新加载信号时发出。
     */
    public function onMasterReload()
    {
        $this->debugPrint("[主进程] 已重载");
    }

    /**
     * 当主进程终止时发出。
     */
    public function onMasterStop()
    {
        $this->debugPrint("[主进程] 已停止");
    }

    /**
     * 在成功建立套接字连接时发出。
     * 多个[工作进程]的情况下，加入的[工作进程]是随机分配的
     * @param {Object} TcpConnection $connection
     */
    public function onConnect(TcpConnection $connection)
    {
        $this->debugPrint("[工作进程 {$connection->worker->id}][连接 {$connection->id}] 已创建");
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
            return $this->send($connection,'↓');
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
     * @param TcpConnection $connection
     * @param Object        $send_buffer 发送字符串
     * @param Bool          $raw         设置是否获取原始数据
     **/
    public function send(TcpConnection $connection, $send_buffer, $raw = false)
    {
        $connection->send($send_buffer, $raw);
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
     * `$connection->worker->connections`等同于`$this->worker->connections`，都是读取当前[工作进程]里的所有连接
     * 客户端数据可以用`$connection`来存储、读取，比如：`$connection->user_id=123;echo $connection->user_id;`
     * @param TcpConnection $connection
     */
    public function connectLogic(TcpConnection $connection)
    {
        $client_id="C{$connection->worker->id}{$connection->id}";
        $connection->client_id =$client_id;

        $client_data=[
            'name'=>"游客-{$client_id}"
        ];

        $connection->client_data =$client_data;

        $this->worker->client_list[$client_id]= $connection;

        $this->debugPrint("[工作进程 {$connection->worker->id}][连接 {$connection->id}][用户 {$client_data['name']}] 已加入");

        return $this->send($connection,$this->success($client_data));
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
            return $this->send($connection,$this->fail($data,'数据格式不正确'));
        }
        $client_id   = $connection->client_id;
        $client_data = $connection->client_data;

        return $this->send($connection, $client_data);
    }

    /**
     * 当连接断开时，广播给所有连接
     * @param TcpConnection $connection
     */
    public function closeLogic(TcpConnection $connection)
    {
        $client_id = $connection->client_id;
        $client_data = $connection->client_data;

        foreach ($this->worker->client_list as $id => $client) {
            $this->send($client,$this->success("[工作进程 {$connection->worker->id}][连接 {$connection->id}][用户 {$client_data['name']}] 已退出"));
        }

        unset($this->worker->client_list[$client_id]);
    }

    //////////////////////////////////////////////////  自定义方法 END  //////////////////////////////////////////////////
}
