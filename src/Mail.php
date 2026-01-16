<?php

/**
 * +----------------------------------------------------------------------
 * | 电子邮件类
 * | 支持：企业邮箱（阿里云、qq）。不支持：个人邮箱。
 * |    aliyun
 * |        https://help.aliyun.com/document_detail/36576.html
 * |    qq
 * |        https://open.work.weixin.qq.com/help2/pc/19886?person_id=1
 * |        https://exmail.qq.com/login
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

use Exception, Error, Closure;
use Dfer\Tools\Constants;
use Dfer\Tools\Statics\{Storage};

class Mail extends Common
{
    private $smtp_host = 'ssl://smtp.qiye.aliyun.com';
    private $smtp_port = 465;
    private $pop_host = 'ssl://pop.qiye.aliyun.com';
    private $pop_port = 995;
    private $imap_host = 'ssl://imap.qiye.aliyun.com';
    private $imap_port = 993;

    private $tag = 0;
    private $keyword_filters = [];
    private $sender_filters = [];

    //调试开关。打印调试信息，存储运行日志
    private $debug = false;
    // 身份验证
    private $auth = true;
    // 登录账号
    private $account = 'mail@dfer.site';
    // 登录密码。加密模式下只允许使用临时密码（获取方式：http://mail.dfer.site/alimail/entries/v5.1/setting/account-security）
    private $password = 'dSBRX4fdI1heQUWJ';
    // 发件人名称
    private $user_name = "Dfer.Site";
    // pfsockopen对象
    private $sock_obj = null;
    // 超时时间（秒）
    private $time_out = 30;

    ////////////////////////////////////////////////// 初始化 START //////////////////////////////////////////////////

    public function __construct($config = [])
    {
        $this->setConfig($config);
    }

    /**
     * 设置默认参数
     * @param Array $config
     */
    public function setConfig($config)
    {
        $this->smtp_host = $config['smtp_host'] ?? $this->smtp_host;
        $this->smtp_port = $config['smtp_port'] ?? $this->smtp_port;
        $this->pop_host = $config['pop_host'] ?? $this->pop_host;
        $this->pop_port = $config['pop_port'] ?? $this->pop_port;
        $this->imap_host = $config['imap_host'] ?? $this->imap_host;
        $this->imap_port = $config['imap_port'] ?? $this->imap_port;
        $this->keyword_filters = $config['keyword_filters'] ?? $this->keyword_filters;
        $this->sender_filters = $config['sender_filters'] ?? $this->sender_filters;

        $this->debug = $config['debug'] ?? $this->debug;
        $this->account = $config['account'] ?? $this->account;
        $this->password = $config['password'] ?? $this->password;
        $this->time_out = $config['time_out'] ?? $this->time_out;
        $this->auth = $config['auth'] ?? $this->auth;
        return $this;
    }

    //////////////////////////////////////////////////  初始化 END  //////////////////////////////////////////////////

    /**
     * 重写父级方法
     */
    public function debugMail()
    {
        if ($this->debug)
            parent::debugMail(func_get_args());
    }

    /**
     * 写入日志
     * @param String $message
     */
    public function logMail($message)
    {
        if ($this->debug) {
            $message = date("H:i:s") . get_current_user() . "[" . getmypid() . "]: " . $message;
            parent::logMail($message);
        }
    }

    /**
     * 加工抓取到的邮件数据
     * @param String $response 源码内容
     * @return mixed
     **/
    public function getData($response)
    {
        $this->debugMail($response);
        $subject = '';
        if (preg_match('/Subject: (.+?)(?=\n\S+:|$)/s', $response, $matches)) {
            $subject = trim($matches[1]);

            // 提取所有编码部分（兼容 utf-8、gb18030、gbk 等）
            if (preg_match_all('/=\?([a-zA-Z0-9-]+)\?B\?([^?]+)\?=/i', $subject, $matches)) {
                $charsets = $matches[1]; // 编码类型（如 utf-8、gb18030）
                $base64_parts = $matches[2]; // Base64 部分
                $decoded_subject = '';

                foreach ($base64_parts as $i => $base64) {
                    $decoded_part = base64_decode($base64);
                    // 转换为目标编码（如 UTF-8）
                    if (function_exists('mb_convert_encoding')) {
                        $decoded_part = mb_convert_encoding($decoded_part, 'UTF-8', $charsets[$i]);
                    }
                    $decoded_subject .= $decoded_part;
                }

                // 如果成功解码，替换原主题
                if (!empty($decoded_subject)) {
                    $subject = $decoded_subject;
                }
            }
        }
        $from = '';
        if (preg_match('/From: .*<([^>]+)>/', $response, $matches)) {
            $from = trim($matches[1]);
        }
        $body_list = [];
        if (preg_match_all('/Content-Transfer-Encoding: base64\s*\n\s*\n([\s\S]+?)\n------=/', $response, $matches)) {
            // 匹配 Content-Transfer-Encoding: base64 后，经过空行，到 \n------= 前的 Base64 内容
            // 处理`纯文本+html`
            foreach ($matches[1] as $base64_content) {
                // 去除空白字符
                $base64_cleaned = preg_replace('/\s+/', '', $base64_content);
                // 解码 base64
                $decoded_content = base64_decode($base64_cleaned);

                // 尝试检测编码
                $encoding = mb_detect_encoding($decoded_content, ['UTF-8', 'GBK', 'GB2312', 'BIG5'], true);

                // 如果检测不到或不是UTF-8，尝试转换为UTF-8
                if ($encoding !== 'UTF-8') {
                    $decoded_content = mb_convert_encoding($decoded_content, 'UTF-8', $encoding ?: 'GBK');
                }

                $body_list[] = $decoded_content;
            }
        } else if (preg_match_all('/Content-Transfer-Encoding:\s*([^\n]+)\s*\n\s*\n([\s\S]+?)\n-/', $response, $matches)) {
            // 匹配 `Content-Transfer-Encoding: {1}（如 8bit、base64）` 后，经过两个换行符（可能含空白字符），到 `\n-` 的{2}
            foreach ($matches[2] as $content) {
                $body_list[] = $content;
            }
            // var_dump($body_list);die;
        } else if (preg_match('/X-QQ-RECHKSPAM: 0\s*\n\s*\n([\s\S]+?)\s*\)/', $response, $matches)) {
            // 处理这种格式的正文
            $text_content = $matches[1];
            // 去除多余空白字符
            $cleaned_content = trim($text_content);
            $body_list[] = $cleaned_content;
        } else if (preg_match('/X-QQ-RECHKSPAM: 0\s*\n\s*\n([\s\S]+?)(?=\n\S+:|$)/s', $response, $matches)) {
            // 检查内容是否可能是 Base64 编码
            $content = trim($matches[1]);
            // 尝试 Base64 解码（如果内容符合 Base64 特征）
            if (preg_match('/^[A-Za-z0-9+\/=]+$/', $content)) {
                $decoded_content = base64_decode($content);
                if ($decoded_content !== false) {
                    $body_list[] = $decoded_content;
                } else {
                    $body_list[] = $content; // 如果解码失败，保留原始内容
                }
            } else {
                $body_list[] = $content; // 如果不是 Base64，直接存储
            }
        } else if (preg_match('/BODY\[1\] \{\d+\}\s*([\s\S]+?)\s*\)/', $response, $matches)) {
            // 处理纯文本
            $base64_content = $matches[1];
            // 去除换行符
            $base64_cleaned = preg_replace('/\s+/', '', $base64_content);
            // 解码
            $decoded_content = base64_decode($base64_cleaned);
            $body_list[] = $decoded_content;
        }

        // var_dump($body_list);die;
        $body = $body_list[0] ?? '';
        // 检查筛选条件
        $keywordMatch = false;
        foreach ($this->keyword_filters ?? [] as $keyword) {
            if (stripos($subject, $keyword) !== false) {
                $keywordMatch = true;
                break;
            }
        }
        $senderMatch = false;
        foreach ($this->sender_filters ?? [] as $filter) {
            if (strtolower($from) === strtolower($filter)) {
                $senderMatch = true;
                break;
            }
        }
        $this->debugMail(compact('keywordMatch', 'senderMatch', 'subject', 'from'));
        // 发件人或者主题符合要求
        if ($keywordMatch || $senderMatch) {
            $result = (object)compact('subject', 'from', 'body');
            return $result;
        }
        return false;
    }

    // ###################################### SMTP START ######################################

    /**
     * 发送邮件
     * @param String $mail_to 收件人邮箱
     * @param String $mail_subject    邮件主题
     * @param String $mail_content    邮件内容
     * @param Int $mail_format 邮件格式（HTML/TXT）
     * @param String $cc  抄送。将邮件的副本同时发送给除了主收件人以外的其他收件人，支持多个邮件(用逗号分隔)，如：a@qq.com,b@qq.com
     * @param String $bcc 密送。将邮件发送给除了主收件人和抄送收件人以外的其他收件人，且这些密送收件人的身份对其他收件人是隐藏的，支持多个邮件(用逗号分隔)，如：a@qq.com,b@qq.com
     * @param String $extend_header   附加头部信息
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
                $sent = false;
                continue;
            }

            // 发送邮件内容。
            if ($this->smtpSend($mail_from, $mail_address, $mail_header, $mail_content)) {
                // 如果发送成功，记录日志。
                $this->logMail("电子邮件已发送至 <{$mail_address}>\n");
            } else {
                $sent = false;
            }

            // 关闭与SMTP服务器的连接。
            fclose($this->sock_obj);

            // 记录断开连接的日志。
            $this->logMail("已断开 {$this->smtp_host}\n");
        }
        // 返回最终的发送状态。
        return $sent;
    }

    /**
     * 通过SMTP发送邮件
     * @param String $mail_from
     * @param String $mail_to
     * @param String $mail_header
     * @param String $mail_content
     */
    public function smtpSend($mail_from, $mail_to, $mail_header, $mail_content)
    {
        // 设置HELO命令的参数，通常这里应该是发送邮件的服务器的域名或IP地址，但这里简单地设置为'localhost'
        $helo = 'localhost';

        // 发送HELO命令给SMTP服务器，并检查是否成功
        if (!$this->smtpPutCmd("HELO", $helo)) {
            return false;
        }

        // 如果启用了SMTP认证
        if ($this->auth) {
            // 发送AUTH LOGIN命令，并附带经过base64编码的账户名
            if (!$this->smtpPutCmd("AUTH LOGIN", base64_encode($this->account))) {
                return false;
            }

            // 发送空命令（实际上是AUTH LOGIN流程的第二步），并附带经过base64编码的密码
            if (!$this->smtpPutCmd("", base64_encode($this->password))) {
                return false;
            }
        }

        // 发送MAIL FROM命令，指定发件人地址
        if (!$this->smtpPutCmd("MAIL", "FROM:<{$mail_from}>")) {
            return false;
        }

        // 发送RCPT TO命令，指定收件人地址
        if (!$this->smtpPutCmd("RCPT", "TO:<{$mail_to}>")) {
            return false;
        }

        // 发送DATA命令，表示接下来的数据是邮件内容
        if (!$this->smtpPutCmd("DATA")) {
            return false;
        }

        // 发送邮件的头部和内容
        if (!$this->smtpPutCmd("{$mail_header}\r\n{$mail_content}", null, false)) {
            return false;
        }

        // 发送邮件内容结束标记
        if (!$this->smtpPutCmd("\r\n.")) {
            return false;
        }

        // 发送QUIT命令，优雅地关闭与SMTP服务器的连接
        if (!$this->smtpPutCmd("QUIT")) {
            return false;
        }
        // 如果所有命令都成功发送，则返回true表示邮件发送成功
        return true;
    }

    /**
     * 打开与SMTP服务器的连接
     * @param String $mail_address
     */
    public function smtpSockOpen($mail_address)
    {
        // 检查是否指定了服务器主机名
        if ($this->smtp_host) {
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
        $this->logMail("尝试连接 {$this->smtp_host}:{$this->smtp_port}\n");
        // 尝试打开到中继主机的socket连接
        $this->sock_obj = @pfsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, $this->time_out);
        // 检查连接是否成功以及SMTP服务器是否响应正常
        if (!($this->sock_obj && $this->smtpResponse())) {
            // 如果连接失败或SMTP服务器响应不正常，则记录错误信息
            $this->logMail("错误：无法连接到中继主机 " . $this->smtp_host . "\n");
            $this->logMail("错误：{$errstr} ({$errno})\n");
            // 返回false表示连接失败
            return false;
        }
        // 如果连接成功且SMTP服务器响应正常，则记录成功信息
        $this->logMail("已连接到 {$this->smtp_host}\n");
        // 返回true表示连接成功
        return true;
    }

    /**
     * 通过邮件地址获取MX记录并连接到相应的SMTP服务器
     * @param String $mail_address
     */
    public function smtpSockOpenMx($mail_address)
    {
        // 从邮件地址中提取域名部分
        $domain = preg_replace("/^.+@([^@]+)$/", "\1", $mail_address);
        // 尝试获取域名的MX记录
        if (!@getmxrr($domain, $mx_host_list)) {
            // 如果无法获取MX记录，则记录错误信息
            $this->logMail("错误：无法解析MX \"{$domain}\"\n");
            // 返回false表示无法获取MX记录
            return false;
        }
        // 遍历MX记录中的主机名
        foreach ($mx_host_list as $host) {
            // 记录尝试连接的日志信息
            $this->logMail("尝试连接mx主机 {$host}:" . $this->smtp_port . "\n");
            // 尝试打开到MX主机的socket连接
            $this->sock_obj = @pfsockopen($host, $this->smtp_port, $errno, $errstr, $this->time_out);
            // 检查连接是否成功以及SMTP服务器是否响应正常
            if (!($this->sock_obj && $this->smtpResponse())) {
                // 如果连接失败或SMTP服务器响应不正常，则记录警告信息
                $this->logMail("警告：无法连接到mx主机 " . $host . "\n");
                $this->logMail("错误： " . $errstr . " (" . $errno . ")\n");
                // 继续尝试下一个MX主机
                continue;
            }
            // 如果连接成功且SMTP服务器响应正常，则记录成功信息
            $this->logMail("已连接到mx主机 {$host}\n");
            // 返回true表示连接成功
            return true;
        }
        // 如果无法连接到任何MX主机，则记录错误信息
        $this->logMail("无法连接到任何mx主机(" . implode(", ", $mx_host_list) . ")\n");
        // 返回false表示连接失败
        return false;
    }

    /**
     * 向SMTP服务器发送命令
     * @param String $cmd
     * @param String $arg
     * @param Bool $need_response   需要返回响应结果
     */
    public function smtpPutCmd($cmd, $arg = null, $need_response = true)
    {
        if ($arg) {
            $cmd = $cmd == "" ? $arg : "{$cmd} {$arg}";
        }
        // 使用fputs函数将命令（后面添加\r\n作为行结束符）写入到sock_obj指定的资源（通常是一个网络连接套接字）中
        fputs($this->sock_obj, "{$cmd}\r\n");
        // 调用logMail方法输出调试信息，显示发送的命令
        $this->logMail("> {$cmd}\n");
        // 调用smtpResponse方法检查SMTP服务器的响应，并返回其结果
        return $need_response ? $this->smtpResponse() : true;
    }

    /**
     * 将构建好的邮件头信息和邮件内容发送给SMTP服务器
     * @param String $mail_header 邮件头信息
     * @param String $mail_content    邮件内容
     */
    public function smtpMessage($mail_header, $mail_content)
    {
        // 将邮件头信息和邮件内容通过fputs函数写入到sock_obj属性指定的资源（通常是一个网络连接套接字）中
        // SMTP协议要求使用\r\n作为行结束符，所以这里在邮件头信息和邮件内容之间添加了\r\n
        fputs($this->sock_obj, "{$mail_header}\r\n{$mail_content}");
        // 调用logMail方法输出调试信息，这里将邮件头信息和邮件内容进行了格式化
        $this->logMail("> " . str_replace("\r\n", "\n> ", "{$mail_header}\n> {$mail_content}\n"));
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
        $this->logMail("{$response}\n");
        // 如果响应不是以2或3开头，表示SMTP服务器返回了一个错误响应
        if (!preg_match("/^[23]/", $response)) {
            // 向SMTP服务器发送QUIT命令，以优雅地关闭连接
            fputs($this->sock_obj, "QUIT\r\n");
            // 从服务器读取最后的响应（虽然这个响应可能不是QUIT命令的直接回应，但通常用于清理）
            fgets($this->sock_obj, 512);
            // $this->logMail("错误：远程主机返回 \"{$response}\"\n");
            // 返回false，表示SMTP服务器的响应不是成功的
            return false;
        }
        // 如果响应是以2或3开头，表示SMTP服务器的响应是成功的（或需要更多信息）
        return true;
    }

    /**
     * 删除电子邮件地址中的注释部分
     * @param String $mail_address 邮件地址
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
     * @param String $mail_address  邮件地址
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

    // ######################################  SMTP END  ######################################

    // ###################################### IMAP START ######################################

    /**
     * 监听新邮件
     * 经测试，建立连接达到一分钟之后，服务器会主动关闭连接，需要重新建立监听
     *
     * 对于 LOGIN、CHECK、NOOP 等简单命令可以使用单次读取。通常单行内返回完整结果，适合 fgets() 单次读取
     * 对于 SELECT、SEARCH、FETCH 、IDLE 等复杂命令应该使用循环读取。会返回多行数据，必须循环读取直到匹配结束标记，
     * @param object $var 变量
     * @return mixed
     **/
    public function monitorMail(Closure $callback)
    {
        $timeout = 0;
        // 设置 php 超时
        set_time_limit($timeout);

        while (true) {
            // 连接邮箱的IMAP服务
            $socket = fsockopen("ssl://{$this->imap_host}", $this->imap_port, $errno, $errstr, 30);
            if (!$socket) {
                $this->debugMail("无法连接IMAP服务器", $errno, $errstr);
                die();
            }
            // 设置 socket 超时
            stream_set_timeout($socket, $timeout);
            // 读取服务器欢迎消息
            fgets($socket);
            // 登录
            $tag = $this->getTag();
            fwrite($socket, "{$tag} LOGIN {$this->account} {$this->password}\r\n");
            $response = fgets($socket);
            $this->debugMail($response);
            if (strpos($response, "{$tag} OK") === false) {
                $this->debugMail("登录失败");
                die();
            }
            // 选择收件箱
            $tag = $this->getTag();
            fwrite($socket, "{$tag} SELECT INBOX\r\n");
            while ($line = fgets($socket)) {
                if (strpos($line, "{$tag} OK") !== false) break;
            }
            // 进入IDLE模式。实时监听邮箱的新邮件通知（无需轮询）
            $tag = $this->getTag();
            fwrite($socket, "{$tag} IDLE\r\n");
            $response = fgets($socket);

            if (trim($response) !== '+ idling') {
                $this->debugMail("无法进入IDLE模式");
                die();
            }
            // 监听服务器通知
            while ($line = fgets($socket)) {
                $this->debugMail('读取邮件数据');
                if (strpos($line, 'EXISTS') !== false) {
                    // 有新邮件到达
                    $this->debugMail('新邮件到达');
                    // 退出IDLE模式获取邮件。必须先用 DONE 退出 IDLE 模式 才能执行其他命令（如 SEARCH、FETCH），这是 IMAP 协议的设计规范
                    fwrite($socket, "DONE\r\n");
                    // 获取未读邮件
                    $tag = $this->getTag();
                    fwrite($socket, "{$tag} SEARCH UNSEEN\r\n");
                    $response = "";
                    while ($line = fgets($socket)) {
                        $response .= $line;
                        if (strpos($line, "{$tag} OK") !== false) break;
                    }
                    // 处理新邮件...
                    $this->debugMail($response);

                    // 邮件ID列表
                    $unseen_ids = [];
                    // 获取匹配的邮件ID。匹配符合 `* SEARCH 某内容` 格式的字符串，并提取 `某内容`
                    if (preg_match('/\* SEARCH (.+)/', $response, $matches)) {
                        $unseen_ids = explode(' ', trim($matches[1]));
                    }

                    if (count($unseen_ids) > 0) {
                        $unseen_id = $unseen_ids[0];

                        $tag = $this->getTag();
                        fwrite($socket, "{$tag} FETCH {$unseen_id} BODY.PEEK[]\r\n");
                        $response = "";
                        while ($line = fgets($socket)) {
                            $response .= $line;
                            if (strpos($line, "{$tag} OK") !== false) break;
                        }

                        $emailContent = $response;

                        // 标记邮件为已读
                        $tag = $this->getTag();
                        fwrite($socket, "{$tag} STORE {$unseen_id} +FLAGS (\\Seen)\r\n");
                        $response = fgets($socket);

                        $data = $this->getData($emailContent);
                        if ($data) {
                            $filteredEmail = [
                                'subject' => $data->subject,
                                'from' => $data->from,
                                'body' => $data->body,
                                'uid' => $unseen_id
                            ];
                            // $this->debugMail($filteredEmail);
                            $callback($filteredEmail);
                        }
                    }

                    break;
                }
            }
            fclose($socket);
            $this->tag = 0;
            $this->debugMail("重置");
        }
    }

    /**
     * 生成命令标签
     * @return string 唯一标签
     */
    private function getTag()
    {
        return 'A' . ++$this->tag;
    }

    // ######################################  IMAP END  ######################################

    // ###################################### POP START ######################################

    /**
     * 检查邮件
     * @param Closure $callback 回调函数
     * @param String $cache_name 缓存文件名。保存于：`/data/fast/`
     * @return mixed
     **/
    public function checkMail(Closure $callback, $cache_name = 'mail')
    {
        // 使用pfsockopen创建持久连接
        $socket = @pfsockopen("ssl://{$this->pop_host}", $this->pop_port, $errno, $errstr, 30);
        if (!$socket) die("连接失败: $errstr ($errno)");

        // 读取欢迎消息
        $response = fgets($socket);

        // POP3认证
        fputs($socket, "USER {$this->account}\r\n");
        $response = fgets($socket);
        if (substr($response, 0, 3) != '+OK') die("USER命令失败: $response");

        fputs($socket, "PASS {$this->password}\r\n");
        $response = fgets($socket);
        if (substr($response, 0, 3) != '+OK') die("PASS命令失败: $response");

        // 获取UIDL列表（唯一标识符列表）
        fputs($socket, "UIDL\r\n");
        $uids = [];
        while (($response = fgets($socket)) != ".\r\n") {
            if (preg_match('/^(\d+)\s+(.+)/', $response, $matches)) {
                $uids[$matches[1]] = $matches[2]; // 邮件编号 => 唯一标识符
            }
        }
        $processedUids = Storage::fast($cache_name) ?: [];
        foreach ($uids as $emailNum => $uid) {
            // 检查是否是新邮件
            if (in_array($uid, $processedUids)) {
                // 跳过已处理的邮件
                continue;
            }
            $this->debugMail($uid);
            // 获取邮件内容
            fputs($socket, "RETR $emailNum\r\n");
            $emailContent = '';
            while (($line = fgets($socket)) !== false) {
                if (trim($line) == ".") break;
                $emailContent .= $line;
            }
            $data = $this->getData($emailContent);
            if ($data) {
                $filteredEmail = [
                    'subject' => $data->subject,
                    'from' => $data->from,
                    'body' => $data->body,
                    'uid' => $uid
                ];
                // $this->debugMail($filteredEmail);
                $callback($filteredEmail);
                $processedUids[] = $uid;
            } else {
                continue;
            }
        }
        Storage::fast($cache_name, $processedUids);
        // 关闭连接
        fputs($socket, "QUIT\r\n");
    }

    // ######################################  POP END  ######################################


}
