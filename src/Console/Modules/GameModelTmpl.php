<?php
declare(strict_types = 1);
namespace Dfer\Tools\Console\Modules;

use Workerman\Worker;
use Workerman\Connection\TcpConnection;

/**
 * +----------------------------------------------------------------------
 * | workerman的console类模板
 * +----------------------------------------------------------------------
 *                      .::::.
 *                    .::::::::.            | AUTHOR: dfer
 *                    :::::::::::           | EMAIL: df_business@qq.com
 *                 ..:::::::::::'           | QQ: 3504725309
 *             '::::::::::::'
 *                .::::::::::
 *           '::::::::::::::..
 *                ..::::::::::::.
 *              ``::::::::::::::::
 *               ::::``:::::::::'        .:::.
 *              ::::'   ':::::'       .::::::::.
 *            .::::'      ::::     .:::::::'::::.
 *           .:::'       :::::  .:::::::::' ':::::.
 *          .::'        :::::.:::::::::'      ':::::.
 *         .::'         ::::::::::::::'         ``::::.
 *     ...:::           ::::::::::::'              ``::.
 *   ```` ':.          ':::::::::'                  ::::..
 *                      '.:::::'                    ':'````..
 * +----------------------------------------------------------------------
 *
 */
class GameModelTmpl extends CommonTmpl
{
    public function onWorkerStart(Worker $worker)
    {
    }
        
    public function onWorkerReload(Worker $worker)
    {
        
        // self::$common_base->debug_print("服务 {$worker->id} 已重载");
        foreach ($worker->connections as $connection) {
            $connection->send(self::$common_base->msg('服务 {$worker->id} 已重载'));
        }
    }
        
    public function onConnect(TcpConnection $connection)
    {
        self::$common_base->debug_print("{$connection->id} {$connection->getRemoteIp()} 建立连接");
        $connection->headers = [
                                        // 'Sec-WebSocket-Protocol: dfer.top',
                                    ];
        https://www.workerman.net/doc/workerman/appendices/about-websocket.html
        $connection->onWebSocketConnect = function ($connection, $header) {
            if (isset($_SERVER['HTTP_SEC_WEBSOCKET_PROTOCOL'])) {
                $protocols = explode(',', $_SERVER['HTTP_SEC_WEBSOCKET_PROTOCOL']);
                $params=json_decode(urldecode($protocols[1]));
                $src = explode('/', $_SERVER['REQUEST_URI'])[1];
                $src = explode('?', $src)[0];
                $get=$_GET;
                // var_dump($src,$get,$params);
                $connection->token=$params->token??'';
                $this->handle_connection($connection);
            }
        };
    }
    public function onMessage(TcpConnection $connection, $message)
    {
        // pingpong
        $connection->lastMessageTime = time();
        if ($message=='↑') {
            $connection->send(self::$common_base->msg([], '↓'));
            return;
        }
                        
        $ret=$this->handle_message($connection, $message);
        if ($ret) {
            $connection->send($ret);
        }
                        
                        
        // 已经处理请求数
        static $request_count = 0;
        // 如果请求数达到1000
        if (++$request_count >= MAX_REQUEST) {
            /*
             * 退出当前进程，主进程会立刻重新启动一个全新进程补充上来
             * 从而完成进程重启
             */
            Worker::stopAll();
        }
    }
    
   
    
    public function onClose(TcpConnection $connection)
    {
        self::$common_base->debug_print("{$connection->id} {$connection->getRemoteIp()} 断开连接");
        // $this->handle_close($connection);
    }
    public function onError(TcpConnection $connection, $code, $msg)
    {
        self::$common_base->debug_print("{$connection->id} {$code} {$msg}");
    }
        
    public function onBufferFull(TcpConnection $connection)
    {
        self::$common_base->debug_print("{$connection->id} 发送缓冲区数据已满");
    }
    public function onBufferDrain(TcpConnection $connection)
    {
        self::$common_base->debug_print("{$connection->id} 发送缓冲区数据已发送完毕");
    }
        
        
        
    // ===============================================================
        
        
    // 当客户端连上来时分配uid
    public function handle_connection($connection)
    {
        global $ws_worker,$global_uid;
        // 为这个链接分配一个uid
        $connection->uid = ++$global_uid;
        // 用户连接
        $ws_worker->list[$connection->uid]['connection'] = $connection;
            
        // 用户数据
        $player_name='player' . $connection->uid;
        $ws_worker->list[$connection->uid]['data'] = array('playing' => 0, 'name' =>$player_name , 'qipan' => array(), 'type' => 0, 'move' => 0);
        
        $data['name'] = $player_name;
        $connection->send(self::$common_base->msg($data));
        self::$common_base->debug_print("{$connection->id} {$connection->getRemoteIp()} {$player_name} {$connection->token} 用户已加入");
    }
         
    // 当客户端发送消息过来时
    public function handle_message($connection, $data)
    {
        global $ws_worker,$db;
        $dataJson = json_decode($data, true);
        if (!$dataJson||!is_array($dataJson)) {
            return self::$common_base->msg($data);
        }
        $my_uid = $connection->uid;
        $your_uid = $ws_worker->list[$my_uid]['data']['playing'];
            
        switch ($data['status']) {
                        case 0:
                            # code...
                            break;
                        
                        default:
                            # code...
                            break;
                    }
    }
        
    // 当客户端断开时，广播给所有客户端
    public function handle_close($connection)
    {
        global $ws_worker;
                
        if (!isset($connection->uid)) {
            return;
        }
        $my_uid = $connection->uid;
            
        // 广播
        foreach ($ws_worker->connections  as $k => $val) {
            $val->send(self::$common_base->msg("用户[{$ws_worker->list[$my_uid]['data']['name']}]已退出"));
        }
        unset($ws_worker->list[$my_uid]);
    }
}
