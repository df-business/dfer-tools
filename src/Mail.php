<?php

/**
 * +----------------------------------------------------------------------
 * | 电子邮件类
 * |    https://help.aliyun.com/document_detail/36576.html
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

namespace Dfer\Tools;

use Dfer\Tools\Constants;

class Mail extends Common
{
    //调试开关。打印调试信息，存储运行日志
    private $debug = false;
    // 服务器地址（默认：SMTP协议）
    private $server_host = 'ssl://smtp.qiye.aliyun.com';
    // 服务器端口号（默认：加密）
    private $server_port = 465;
    // 身份验证
    private $auth = true;
    // 登录账号
    private $account = 'mail@dfer.site';
    // 登录密码。加密模式下只允许使用临时密码（获取方式：http://mail.dfer.site/alimail/entries/v5.1/setting/account-security）
    private $password = 'dSBRX4fdI1heQUWJ';
    // 发件人名称
    private $user_name = "Dfer.Site";
    // 日志文件路径
    private $log_file = "";
    // pfsockopen对象
    private $sock_obj = null;
    // 超时时间（秒）
    private $time_out = 30;

    public function __construct()
    {
        $root = $this->getRootPath();
        $this->log_file = $this->str("{root}/data/logs/mail/{dir}/{file}.log", ["root" => $root, "dir" => date('Ym'), "file" => date('d')]);
        $this->writeFile(null, $this->log_file, "a");
    }

    /**
     * 设置默认参数
     * @param {Object} $config
     */
    public function setDefaultConfig($config)
    {
        $this->debug = $config['debug'] ?? $this->debug;
        $this->server_port = $config['server_port'] ?? $this->server_port;
        $this->server_host = $config['server_host'] ?? $this->server_host;
        $this->account = $config['account'] ?? $this->account;
        $this->password = $config['password'] ?? $this->password;
        $this->log_file = $config['log_file'] ?? $this->log_file;
        $this->time_out = $config['time_out'] ?? $this->time_out;
        $this->auth = $config['auth'] ?? $this->auth;
        return $this;
    }

    /**
     * 发送邮件
     * @param {Object} $mail_to 收件人邮箱
     * @param {Object} $mail_subject    邮件主题
     * @param {Object} $mail_content    邮件内容
     * @param {Object} $mail_format 邮件格式（HTML/TXT）
     * @param {Object} $cc  抄送。将邮件的副本同时发送给除了主收件人以外的其他收件人，支持多个邮件(用逗号分隔)，如：a@qq.com,b@qq.com
     * @param {Object} $bcc 密送。将邮件发送给除了主收件人和抄送收件人以外的其他收件人，且这些密送收件人的身份对其他收件人是隐藏的，支持多个邮件(用逗号分隔)，如：a@qq.com,b@qq.com
     * @param {Object} $extend_header   附加头部信息
     */
    public function send($mail_to, $mail_subject, $mail_content, $mail_format = Constants::HTML, $cc = "", $bcc = "", $extend_header = "")
    {
        $user_name = $this->user_name;
        $mail_from = $this->account;
        // 获取处理过的发件人地址，移除可能存在的注释部分。
        $mail_from = $this->getMailAddress($this->clearRemark($mail_from));

        // 处理邮件内容，确保内容中的句点（.）不会被误解为邮件头的一部分，这在发送HTML邮件时尤其重要。
        $mail_content = preg_replace("/(^|(\r\n))(\.)/", "\1.\3", $mail_content);

        // 初始化邮件头部信息，设置MIME版本为1.0。
        $mail_header = "MIME-Version:1.0\r\n";

        // 如果邮件格式为HTML，则设置内容类型为text/html。
        if ($mail_format == "HTML") {
            $mail_header .= "Content-Type:text/html\r\n";
        }

        // 添加收件人信息到邮件头部。
        $mail_header .= "To: {$mail_to}\r\n";

        // 如果抄送（CC）字段不为空，则添加抄送信息到邮件头部。
        if ($cc) {
            $mail_header .= "Cc: {$cc}\r\n";
        }

        // 添加发件人信息到邮件头部，尖括号内作为邮箱地址。
        $mail_header .= "From: {$user_name}<{$mail_from}>\r\n";

        // 添加邮件主题到邮件头部。
        $mail_header .= "Subject: {$mail_subject}\r\n";

        // 添加额外的头部信息。
        $mail_header .= $extend_header;

        // 添加当前日期和时间到邮件头部。
        $mail_header .= "Date: " . date("r") . "\r\n";

        // 添加邮件发送客户端信息。
        $mail_header .= "X-Mailer:By Dfer.Site (PHP/" . phpversion() . ")\r\n";

        // 生成一个唯一的消息ID，用于标识这封邮件。
        list($msec, $sec) = explode(" ", microtime());
        $mail_header .= "Message-ID: <" . date("YmdHis", $sec) . "." . ($msec * 1000000) . "." . $mail_from . ">\r\n";

        // 将收件人地址（包括抄送和密送）转换为数组，以便逐个发送邮件。
        $mail_list = explode(",", $this->clearRemark($mail_to));
        if ($cc) {
            $mail_list = array_merge($mail_list, explode(",", $this->clearRemark($cc)));
        }
        if ($bcc) {
            $mail_list = array_merge($mail_list, explode(",", $this->clearRemark($bcc)));
        }

        // 初始化发送状态为true，假设所有邮件都能成功发送。
        $sent = true;

        // 遍历收件人数组，逐个发送邮件。
        foreach ($mail_list as $mail_address) {
            // 获取处理过的收件人地址，移除可能存在的注释部分。
            $mail_address = $this->getMailAddress($mail_address);

            // 尝试打开与SMTP服务器的连接。
            if (!$this->smtpSockOpen($mail_address)) {
                // 如果连接失败，记录错误日志，并将发送状态设置为false。
                $this->logWrite("错误：无法发送电子邮件至 {$mail_address}\n");
                $sent = false;
                continue;
            }

            // 发送邮件内容。
            if ($this->smtpSend($mail_from, $mail_address, $mail_header, $mail_content)) {
                // 如果发送成功，记录日志。
                $this->logWrite("电子邮件已发送至 <{$mail_address}>\n");
            } else {
                // 如果发送失败，记录错误日志，并将发送状态设置为false。
                $this->logWrite("错误：无法发送电子邮件至 <{$mail_address}>\n");
                $sent = false;
            }

            // 关闭与SMTP服务器的连接。
            fclose($this->sock_obj);

            // 记录断开连接的日志。
            $this->logWrite("已断开与远程主机的连接\n");
        }
        // 返回最终的发送状态。
        return $sent;
    }

    /**
     * 通过SMTP发送邮件
     * @param {Object} $mail_from
     * @param {Object} $mail_to
     * @param {Object} $mail_header
     * @param {Object} $mail_content
     */
    public function smtpSend($mail_from, $mail_to, $mail_header, $mail_content)
    {
        // 设置HELO命令的参数，通常这里应该是发送邮件的服务器的域名或IP地址，但这里简单地设置为'localhost'
        $helo = 'localhost';

        // 发送HELO命令给SMTP服务器，并检查是否成功
        if (!$this->smtpPutCmd("HELO", $helo)) {
            return $this->smtpError("发送HELO命令");
        }

        // 如果启用了SMTP认证
        if ($this->auth) {
            // 发送AUTH LOGIN命令，并附带经过base64编码的账户名
            if (!$this->smtpPutCmd("AUTH LOGIN", base64_encode($this->account))) {
                return $this->smtpError("发送AUTH LOGIN命令");
            }

            // 发送空命令（实际上是AUTH LOGIN流程的第二步），并附带经过base64编码的密码
            if (!$this->smtpPutCmd("", base64_encode($this->password))) {
                return $this->smtpError("发送密码认证命令");
            }
        }

        // 发送MAIL FROM命令，指定发件人地址
        if (!$this->smtpPutCmd("MAIL", "FROM:<{$mail_from}>")) {
            return $this->smtpError("发送`MAIL FROM`命令");
        }

        // 发送RCPT TO命令，指定收件人地址
        if (!$this->smtpPutCmd("RCPT", "TO:<{$mail_to}>")) {
            return $this->smtpError("发送`RCPT TO`命令");
        }

        // 发送DATA命令，表示接下来的数据是邮件内容
        if (!$this->smtpPutCmd("DATA")) {
            return $this->smtpError("发送DATA命令");
        }

        // 发送邮件的头部和内容
        if (!$this->smtpPutCmd("{$mail_header}\r\n{$mail_content}", null, false)) {
            return $this->smtpError("发送消息");
        }

        // 发送邮件内容结束标记
        if (!$this->smtpPutCmd("\r\n.")) {
            return $this->smtpError("发送 `<CR><LF>.<CR><LF> [EOM]`");
        }

        // 发送QUIT命令，优雅地关闭与SMTP服务器的连接
        if (!$this->smtpPutCmd("QUIT")) {
            return $this->smtpError("发送QUIT命令");
        }
        // 如果所有命令都成功发送，则返回true表示邮件发送成功
        return true;
    }

    /**
     * 打开与SMTP服务器的连接
     * @param {Object} $mail_address
     */
    public function smtpSockOpen($mail_address)
    {
        // 检查是否指定了服务器主机名
        if ($this->server_host) {
            // 如果指定了，则尝试连接到指定的中继主机
            return $this->smtpSockOpenRelay();
        } else {
            // 如果没有指定，则尝试通过邮件地址获取MX记录并连接
            return $this->smtpSockOpenMx($mail_address);
        }
    }

    /**
     * 连接到指定的中继SMTP服务器
     */
    public function smtpSockOpenRelay()
    {
        // 记录尝试连接的日志信息
        $this->logWrite("尝试连接 " . $this->server_host . ":" . $this->server_port . "\n");
        // 尝试打开到中继主机的socket连接
        $this->sock_obj = @pfsockopen($this->server_host, $this->server_port, $errno, $errstr, $this->time_out);
        // 检查连接是否成功以及SMTP服务器是否响应正常
        if (!($this->sock_obj && $this->smtpResponse())) {
            // 如果连接失败或SMTP服务器响应不正常，则记录错误信息
            $this->logWrite("错误：无法连接到中继主机 " . $this->server_host . "\n");
            $this->logWrite("错误：{$errstr} ({$errno})\n");
            // 返回false表示连接失败
            return false;
        }
        // 如果连接成功且SMTP服务器响应正常，则记录成功信息
        $this->logWrite("已连接到中继主机 " . $this->server_host . "\n");
        // 返回true表示连接成功
        return true;
    }

    /**
     * 通过邮件地址获取MX记录并连接到相应的SMTP服务器
     * @param {Object} $mail_address
     */
    public function smtpSockOpenMx($mail_address)
    {
        // 从邮件地址中提取域名部分
        $domain = preg_replace("/^.+@([^@]+)$/", "\1", $mail_address);
        // 尝试获取域名的MX记录
        if (!@getmxrr($domain, $mx_host_list)) {
            // 如果无法获取MX记录，则记录错误信息
            $this->logWrite("错误：无法解析MX \"{$domain}\"\n");
            // 返回false表示无法获取MX记录
            return false;
        }
        // 遍历MX记录中的主机名
        foreach ($mx_host_list as $host) {
            // 记录尝试连接的日志信息
            $this->logWrite("尝试连接 {$host}:" . $this->server_port . "\n");
            // 尝试打开到MX主机的socket连接
            $this->sock_obj = @pfsockopen($host, $this->server_port, $errno, $errstr, $this->time_out);
            // 检查连接是否成功以及SMTP服务器是否响应正常
            if (!($this->sock_obj && $this->smtpResponse())) {
                // 如果连接失败或SMTP服务器响应不正常，则记录警告信息
                $this->logWrite("警告：无法连接到mx主机 " . $host . "\n");
                $this->logWrite("错误： " . $errstr . " (" . $errno . ")\n");
                // 继续尝试下一个MX主机
                continue;
            }
            // 如果连接成功且SMTP服务器响应正常，则记录成功信息
            $this->logWrite("已连接到mx主机 {$host}\n");
            // 返回true表示连接成功
            return true;
        }
        // 如果无法连接到任何MX主机，则记录错误信息
        $this->logWrite("错误：无法连接到任何mx主机(" . implode(", ", $mx_host_list) . ")\n");
        // 返回false表示连接失败
        return false;
    }

    /**
     * 向SMTP服务器发送命令
     * @param {Object} $cmd
     * @param {Object} $arg
     * @param {Object} $need_response   需要返回响应结果
     */
    public function smtpPutCmd($cmd, $arg = null, $need_response = true)
    {
        if ($arg) {
            $cmd = $cmd == "" ? $arg : "{$cmd} {$arg}";
        }
        // 使用fputs函数将命令（后面添加\r\n作为行结束符）写入到sock_obj指定的资源（通常是一个网络连接套接字）中
        fputs($this->sock_obj, "{$cmd}\r\n");
        // 调用logWrite方法输出调试信息，显示发送的命令
        $this->logWrite("> {$cmd}\n");
        // 调用smtpResponse方法检查SMTP服务器的响应，并返回其结果
        return $need_response ? $this->smtpResponse() : true;
    }

    /**
     * 将构建好的邮件头信息和邮件内容发送给SMTP服务器
     * @param {Object} $mail_header 邮件头信息
     * @param {Object} $mail_content    邮件内容
     */
    public function smtpMessage($mail_header, $mail_content)
    {
        // 将邮件头信息和邮件内容通过fputs函数写入到sock_obj属性指定的资源（通常是一个网络连接套接字）中
        // SMTP协议要求使用\r\n作为行结束符，所以这里在邮件头信息和邮件内容之间添加了\r\n
        fputs($this->sock_obj, "{$mail_header}\r\n{$mail_content}");
        // 调用logWrite方法输出调试信息，这里将邮件头信息和邮件内容进行了格式化
        $this->logWrite("> " . str_replace("\r\n", "\n> ", "{$mail_header}\n> {$mail_content}\n"));
        // 函数返回true，表示邮件消息已经成功写入到sock_obj指定的资源中
        return true;
    }

    /**
     * 检查SMTP服务器的响应是否表示成功
     */
    public function smtpResponse()
    {
        // 从sock_obj指定的资源（通常是一个网络连接套接字）中读取一行（最多512个字符），并移除其中的\r\n行结束符
        $response = str_replace("\r\n", "", fgets($this->sock_obj, 512));
        $this->logWrite("{$response}\n");
        // 如果响应不是以2或3开头，表示SMTP服务器返回了一个错误响应
        if (!preg_match("/^[23]/", $response)) {
            // 向SMTP服务器发送QUIT命令，以优雅地关闭连接
            fputs($this->sock_obj, "QUIT\r\n");
            // 从服务器读取最后的响应（虽然这个响应可能不是QUIT命令的直接回应，但通常用于清理）
            fgets($this->sock_obj, 512);
            $this->logWrite("错误：远程主机返回 \"{$response}\"\n");
            // 返回false，表示SMTP服务器的响应不是成功的
            return false;
        }
        // 如果响应是以2或3开头，表示SMTP服务器的响应是成功的（或需要更多信息）
        return true;
    }

    /**
     * 写入smtp错误日志
     * @param {Object} $string
     */
    public function smtpError($string)
    {
        $this->logWrite("错误: 在 {$string} 的时候发生错误\n");
        return false;
    }

    /**
     * 写入日志
     * @param {Object} $message
     */
    public function logWrite($message)
    {
        if (!$this->debug) {
            return false;
        }
        // 检查log_file属性是否为空字符串，如果为空则不写入日志，直接返回true
        if ($this->log_file == "") {
            return true;
        }
        // 格式化消息，添加时间戳、当前用户和进程ID
        // 注意：get_current_user()函数在某些SAPI（如CLI）下可能不可用，且getmypid()返回的是当前PHP脚本的进程ID
        $message = date("M d H:i:s ") . get_current_user() . "[" . getmypid() . "]: " . $message;

        // 检查日志文件是否存在，以及是否能以追加模式打开
        if (!@file_exists($this->log_file) || !($fp = @fopen($this->log_file, "a"))) {
            // 如果文件不存在或无法打开，则输出警告信息，并返回false
            echo "警告：无法打开日志文件 \"" . $this->log_file . "\"\n";
            return false;
        }
        // 对文件进行独占锁定，以避免并发写入时的数据竞争
        flock($fp, LOCK_EX);
        // 将格式化后的消息写入文件
        fputs($fp, $message);
        // 关闭文件句柄
        fclose($fp);
        // 返回true，表示日志写入成功
        return true;
    }

    /**
     * 删除电子邮件地址中的注释部分
     * @param {Object} $mail_address 邮件地址
     */
    public function clearRemark($mail_address)
    {
        // 定义一个正则表达式，用于匹配括号内的内容（即电子邮件地址中的注释部分）
        $comment = "/\([^()]*\)/";
        // 使用preg_match函数检查$mail_address中是否存在匹配正则表达式的部分
        while (preg_match($comment, $mail_address)) {
            // 使用preg_replace函数将匹配到的部分替换为空字符串，即删除注释部分
            $mail_address = preg_replace($comment, "", $mail_address);
        }
        return $mail_address;
    }
    /**
     * 清理和提取邮件地址
     * @param {Object} $mail_address  邮件地址
     */
    public function getMailAddress($mail_address)
    {
        // 第一步：去除$mail_address字符串中的空格、制表符、回车符和换行符
        $mail_address = preg_replace("/([ \t\r\n])+/", "", $mail_address);
        // 第二步：从$mail_address中提取邮件地址的核心部分，即去除尖括号及其包含的内容之外的部分
        $mail_address = preg_replace("/^.*<(.+)>.*$/", "\\1", $mail_address);
        // 返回处理后的$mail_address，此时它应该只包含邮件地址的核心部分，且没有空格、制表符、回车符和换行符
        return $mail_address;
    }
}
