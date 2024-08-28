<?php

/**
 * +----------------------------------------------------------------------
 * | 电子邮件类
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

class Mail
{


    private $debug = false; //调试开关。是否显示发送的调试信息
    private $relay_host = 'ssl://smtp.mxhichina.com'; //SMTP服务器。QQ 邮箱的服务器地址;;
    private $smtp_port = 465; // SMTP服务器端口。smtp 服务器的远程服务器端口号
    private $auth = true; //这里面的一个true是表示使用身份验证,否则不使用身份验证
    private $user = 'df@df315.top'; // SMTP服务器的用户邮箱。SMTP服务器的用户帐号。授权登录的账号;
    private $pass = 'Dbl159357'; // SMTP服务器的用户密码（我的163邮箱的授权码）jdmmfnrzwioucajb。授权登录的密码;
    private $host_name = "localhost"; //is used in HELO command
    private $log_file = "";
    private $sock = FALSE;
    private $time_out = 30; //is used in pfsockopen()

    /**
     * 获取对象实例
     */
    public static function instance($config = [])
    {
        $this->debug = $config['debug'] ?? $this->debug;
        $this->smtp_port = $config['smtp_port'] ?? $this->smtp_port;
        $this->relay_host = $config['relay_host'] ?? $relay_host;
        $this->time_out = $config['time_out'] ?? $this->time_out;
        $this->auth = $config['auth'] ?? $this->auth;
        $this->user = $config['user'] ?? $this->user;
        $this->pass = $config['pass'] ?? $this->pass;
        $this->host_name = $config['host_name'] ?? $this->host_name;
        $this->log_file = $config['log_file'] ?? $this->log_file;
        $this->sock = $config['sock'] ?? $this->sock;
        return $this;
    }



    /**
     * 发送邮件
     * @param {Object} $smtpemailto    收件人邮箱
     * @param {Object} $mailtitle    邮件主题
     * @param {Object} $mailcontent    邮件内容
     * @param {Object} $mailtype    邮件格式（HTML/TXT）,TXT为文本邮件
     */
    public function send($smtpemailto, $mailtitle, $mailcontent, $mailtype = 'HTML')
    {
        $state = $this->sendmail($smtpemailto, $this->user, $mailtitle, $mailcontent, $mailtype, "", "", "");
        return $state;
    }

    /* Main Function */

    /**
     *
     * @param {Object} $to
     * @param {Object} $from
     * @param {Object} $subject
     * @param {Object} $body
     * @param {Object} $mailtype
     * @param {Object} $cc
     * @param {Object} $bcc
     * @param {Object} $additional_headers
     */
    function sendmail($to, $from, $subject, $body, $mailtype, $cc, $bcc, $additional_headers)
    {
        $mail_from = $this->getAddress($this->stripComment($from));
        $body = preg_replace("/(^|(\r\n))(\.)/", "\1.\3", $body);
        $header = "MIME-Version:1.0\r\n";
        if ($mailtype == "HTML") {
            $header .= "Content-Type:text/html\r\n";
        }
        $header .= "To: " . $to . "\r\n";
        if ($cc != "") {
            $header .= "Cc: " . $cc . "\r\n";
        }
        $header .= "From: $from<" . $from . ">\r\n";
        $header .= "Subject: " . $subject . "\r\n";
        $header .= $additional_headers;
        $header .= "Date: " . date("r") . "\r\n";
        $header .= "X-Mailer:By Redhat (PHP/" . phpversion() . ")\r\n";
        list($msec, $sec) = explode(" ", microtime());
        $header .= "Message-ID: <" . date("YmdHis", $sec) . "." . ($msec * 1000000) . "." . $mail_from . ">\r\n";
        $TO = explode(",", $this->stripComment($to));
        if ($cc != "") {
            $TO = array_merge($TO, explode(",", $this->stripComment($cc)));
        }
        if ($bcc != "") {
            $TO = array_merge($TO, explode(",", $this->stripComment($bcc)));
        }
        $sent = TRUE;
        foreach ($TO as $rcpt_to) {
            $rcpt_to = $this->getAddress($rcpt_to);
            if (!$this->smtpSockOpen($rcpt_to)) {
                $this->logWrite("Error: Cannot send email to " . $rcpt_to . "\n");
                $sent = FALSE;
                continue;
            }
            if ($this->smtp_send($this->host_name, $mail_from, $rcpt_to, $header, $body)) {
                $this->logWrite("E-mail has been sent to <" . $rcpt_to . ">\n");
            } else {
                $this->logWrite("Error: Cannot send email to <" . $rcpt_to . ">\n");
                $sent = FALSE;
            }
            fclose($this->sock);
            $this->logWrite("Disconnected from remote host\n");
        }
        return $sent;
    }

    /* Private Functions */
    function smtp_send($helo, $from, $to, $header, $body = "")
    {
        if (!$this->smtpPutCmd("HELO", $helo)) {
            return $this->smtpError("sending HELO command");
        }
        #auth
        if ($this->auth) {
            if (!$this->smtpPutCmd("AUTH LOGIN", base64_encode($this->user))) {
                return $this->smtpError("sending HELO command");
            }

            if (!$this->smtpPutCmd("", base64_encode($this->pass))) {
                return $this->smtpError("sending HELO command");
            }
        }

        if (!$this->smtpPutCmd("MAIL", "FROM:<" . $from . ">")) {
            return $this->smtpError("sending MAIL FROM command");
        }
        if (!$this->smtpPutCmd("RCPT", "TO:<" . $to . ">")) {
            return $this->smtpError("sending RCPT TO command");
        }
        if (!$this->smtpPutCmd("DATA")) {
            return $this->smtpError("sending DATA command");
        }
        if (!$this->smtpMessage($header, $body)) {
            return $this->smtpError("sending message");
        }
        if (!$this->smtpEom()) {
            return $this->smtpError("sending <CR><LF>.<CR><LF> [EOM]");
        }
        if (!$this->smtpPutCmd("QUIT")) {
            return $this->smtpError("sending QUIT command");
        }
        return TRUE;
    }

    function smtpSockOpen($address)
    {
        if ($this->relay_host == "") {
            return $this->smtpSockOpenMx($address);
        } else {
            return $this->smtpSockOpenRelay();
        }
    }

    function smtpSockOpenRelay()
    {
        $this->logWrite("Trying to " . $this->relay_host . ":" . $this->smtp_port . "\n");
        $this->sock = @pfsockopen($this->relay_host, $this->smtp_port, $errno, $errstr, $this->time_out);
        if (!($this->sock && $this->smtpOk())) {
            $this->logWrite("Error: Cannot connenct to relay host " . $this->relay_host . "\n");
            $this->logWrite("Error: " . $errstr . " (" . $errno . ")\n");
            return FALSE;
        }

        $this->logWrite("Connected to relay host " . $this->relay_host . "\n");
        return TRUE;
        ;
    }

    function smtpSockOpenMx($address)
    {
        $domain = preg_replace("/^.+@([^@]+)$/", "\1", $address);
        if (!@getmxrr($domain, $MXHOSTS)) {
            $this->logWrite("Error: Cannot resolve MX \"" . $domain . "\"\n");
            return FALSE;
        }
        foreach ($MXHOSTS as $host) {
            $this->logWrite("Trying to " . $host . ":" . $this->smtp_port . "\n");
            $this->sock = @pfsockopen($host, $this->smtp_port, $errno, $errstr, $this->time_out);
            if (!($this->sock && $this->smtpOk())) {
                $this->logWrite("Warning: Cannot connect to mx host " . $host . "\n");
                $this->logWrite("Error: " . $errstr . " (" . $errno . ")\n");
                continue;
            }
            $this->logWrite("Connected to mx host " . $host . "\n");
            return TRUE;
        }
        $this->logWrite("Error: Cannot connect to any mx hosts (" . implode(", ", $MXHOSTS) . ")\n");
        return FALSE;
    }

    function smtpMessage($header, $body)
    {
        fputs($this->sock, $header . "\r\n" . $body);
        $this->smtpDebug("> " . str_replace("\r\n", "\n" . "> ", $header . "\n> " . $body . "\n> "));
        return TRUE;
    }

    function smtpEom()
    {
        fputs($this->sock, "\r\n.\r\n");
        $this->smtpDebug(". [EOM]\n");
        return $this->smtpOk();
    }

    function smtpOk()
    {
        $response = str_replace("\r\n", "", fgets($this->sock, 512));
        $this->smtpDebug($response . "\n");
        if (!preg_match("/^[23]/", $response)) {
            fputs($this->sock, "QUIT\r\n");
            fgets($this->sock, 512);
            $this->logWrite("Error: Remote host returned \"" . $response . "\"\n");
            return FALSE;
        }
        return TRUE;
    }

    function smtpPutCmd($cmd, $arg = "")
    {
        if ($arg != "") {
            if ($cmd == "")
                $cmd = $arg;
            else
                $cmd = $cmd . " " . $arg;
        }
        fputs($this->sock, $cmd . "\r\n");
        $this->smtpDebug("> " . $cmd . "\n");
        return $this->smtpOk();
    }

    function smtpError($string)
    {
        $this->logWrite("Error: Error occurred while " . $string . ".\n");
        return FALSE;
    }

    function logWrite($message)
    {
        $this->smtpDebug($message);
        if ($this->log_file == "") {
            return TRUE;
        }
        $message = date("M d H:i:s ") . get_current_user() . "[" . getmypid() . "]: " . $message;
        if (!@file_exists($this->log_file) || !($fp = @fopen($this->log_file, "a"))) {
            $this->smtpDebug("Warning: Cannot open log file \"" . $this->log_file . "\"\n");
            return FALSE;
            ;
        }
        flock($fp, LOCK_EX);
        fputs($fp, $message);
        fclose($fp);
        return TRUE;
    }


    function stripComment($address)
    {
        $comment = "/\([^()]*\)/";
        while (preg_match($comment, $address)) {
            $address = preg_replace($comment, "", $address);
        }
        return $address;
    }

    function getAddress($address)
    {
        $address = preg_replace("/([ \t\r\n])+/", "", $address);
        $address = preg_replace("/^.*<(.+)>.*$/", "\1", $address);
        return $address;
    }

    function smtpDebug($message)
    {
        if ($this->debug) {
            echo $message;
        }
    }
}
