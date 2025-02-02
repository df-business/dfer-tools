<?php

/**
 * +----------------------------------------------------------------------
 * | 常用的方法
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

use DOMDocument, Closure, Exception, Error, Throwable, DateTime, stdClass;
use Dfer\Tools\Constants;

class Common
{
    // 静态属性，保存单例实例
    private static $instance;

    use ImgTrait, FilesTrait;

    /**
     * 简介
     *
     **/
    public function about()
    {
        $host = 'http://www.dfer.site';
        header("Location:" . $host);
        return $host;
    }

    /**
     * 打印
     **/
    public function print($str = null)
    {
        echo json_encode($str, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }

    /**
     * 把mysql导出的json文本拼接成数组字符串
     **/
    public function mySqlJsonToArray($str = null)
    {
        $arr = json_decode($str);
        $item = $arr->RECORDS;
        // var_dump($item);

        $name = [];
        foreach ($item as $key => $value) {
            $name[] = $value->name;
        }
        $result = sprintf('["%s"]', join('","', $name));
        return $result;
    }

    /**
     * 输出json，然后终止当前请求
     * @param {Object} $status    状态码。0:正常 其余数字:失败
     * 比如：
     * 100 未提交数据
     * 101 不要重复提交数据
     */
    public function showJson($status = 0, $data = array(), $success_msg = '', $fail_msg = '')
    {
        $msg = $status === 0 ? ($success_msg ?: '操作成功') : ($fail_msg ?: '操作失败');

        $ret = array(
            'status' => $status,
            'msg' => $msg
        );
        if ($data) {
            $ret['data'] = $data;
        }

        $this->showJsonBase($ret);
    }

    /**
     *    输出json数据
     * @param {Object} $return    数据
     * @param {Object} $to_json true:json对象 false:json字符串
     */
    public function showJsonBase($return = array(), $to_json = true)
    {
        if ($to_json)
            //json格式
            header('content-type:application/json;charset=utf-8');
        //中文不加密
        die(json_encode($return, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 是微信端则返回true
     */
    public function isWeixin()
    {
        if (empty($_SERVER['HTTP_USER_AGENT']) || strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false && strpos($_SERVER['HTTP_USER_AGENT'], 'Windows Phone') === false) {
            return false;
        }
        return true;
    }

    /**
     * http与https相互转换
     */
    public function httpAndhttps()
    {
        if ($_SERVER["HTTPS"] == "on") {
            $xredir = "http://" . $_SERVER["SERVER_NAME"] .
                $_SERVER["REQUEST_URI"];
            header("Location: " . $xredir);
        } else {
            $xredir = "https://" . $_SERVER["SERVER_NAME"] .
                $_SERVER["REQUEST_URI"];
            header("Location: " . $xredir);
        }
    }

    /**
     * 将时间数据转化为正常的时间格式
     * eg:
     * getTime(1709091401,Common::TIME_FULL)
     * getTime(1709091401,"Y/m/d H:i:s")
     * getTime("2024-02-28 11:36:41","Y/m/d H:i:s")
     * getTime(null,"Y/m/d H:i:s")
     * @param {Object} $time 时间数据。int 时间戳(1709091401) string 时间字符串(2024-02-28 11:36:41)
     * @param {Object} $type 类型
     * @return {Object} 正常时间格式(2024-02-28 11:36:41)
     */
    public function getTime($time = null, $type = Constants::TIME_FULL)
    {
        $format = $type;
        if (empty($time)) {
            return date($format);
        }

        if (is_numeric($time)) {
            // 将时间戳转化为正常的时间格式
            return date($format, $time);
        } else {
            // 将时间字符串转化为时间戳，格式化之后转化为正常的时间格式
            //date_default_timezone_set('Asia/Shanghai'); //设置为东八区上海时间
            return date($format, strtotime($time));
        }
    }

    /**
     * 时间戳转UTC时间
     * UTC即国际时间，在UTC基础上加8小时即中国时间
     * @param {Object} $time    时间戳
     */
    public function getUtcTime($time)
    {
        date_default_timezone_set("UTC");
        $time = date("Y-m-d\TH:i:s.z", $time) . 'Z';
        return $time;
    }

    /**
     * 将时间戳转换为一个GMT时间字符串，
     * @param {Object} $timestamp 时间戳
     * @return {Object} 以“ISO 8601”格式表示的GMT（格林威治标准时间）字符串，例如 "2023-09-13T12:34:56Z"
     */
    public function gmtIso8601($time)
    {
        $dtStr = date("c", $time);
        $mydatetime = new DateTime($dtStr);
        $expiration = $mydatetime->format(DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration . "Z";
    }

    /**
     * unicode加密
     * @param {Object} $str
     */
    public function unicodeEncode($str)
    {
        //split word
        preg_match_all('/./u', $str, $matches);

        $unicodeStr = "";
        foreach ($matches[0] as $m) {
            //拼接
            $unicodeStr .= "&#" . base_convert(bin2hex(iconv('UTF-8', "UCS-4", $m)), 16, 10);
        }
        return $unicodeStr;
    }

    public function replaceUnicodeEscapeSequence($match)
    {
        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
    }

    /**
     * unicode解密
     * @param {Object} $unicode_str
     */
    public function unicodeDecode($unicode_str)
    {
        $json = '["' . $unicode_str . '"]';
        $arr = json_decode($json, true);
        if (empty($arr)) {
            return '';
        }
        return is_array($arr) ? $arr[0] : $arr;

        // $str = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $name);
        // return $str;
    }

    /**
     * HTTP请求（支持HTTP/HTTPS，支持GET/POST）
     *
     * @param String $url 网址。http://www.dfer.site
     * @param Array $data 参数内容。["a"=>123]
     * @param Int $type 请求类型。默认post
     * @param Array $header header参数。["Content-Type: application/json"]
     * @param Array $cookie cookie参数。['name'=>'xxx']
     * @return Json json对象
     **/
    public function httpRequest($url, $data = null, $type = Constants::REQ_POST, $header = null, $cookie = null, $timeout = 50)
    {
        //初始化cURL会话
        $curl = curl_init();
        switch ($type) {
            case Constants::REQ_JSON:
                // json字符串
                if (is_array($data)) {
                    // 不对非 ASCII 字符（如中文、日文等 Unicode 字符）进行转义
                    $data = json_encode($data, JSON_UNESCAPED_UNICODE);
                }
                // 不包含头部信息
                curl_setopt($curl, CURLOPT_HEADER, false);
                // 默认请求头
                $header = is_array($header) ? array_merge([
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Content-Length' => strlen($data),
                    'Cache-Control' => 'no-cache',
                    'Pragma' => 'no-cache'
                ], $header) : $header;
                // var_dump($header);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case Constants::REQ_GET:
                // get数组
                //判断data是否有数据
                if (!empty($data)) {
                    $url .= '?' . $this->arr2url($data);
                    $data = null;
                }
                break;
            case Constants::REQ_POST:
                // post数组
                //判断data是否有数据
                if (!empty($data)) {
                    // 解析数组字符串
                    $data = is_array($data) ? $data : json_decode($data);
                    // 发送一个 POST 请求
                    curl_setopt($curl, CURLOPT_POST, true);
                    // 指定要发送到服务器的数据
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $this->arr2url($data, false));
                }
                break;
            default:
                # code...
                break;
        }

        //设置header头
        if (!empty($header)) {
            $header_list = [];
            foreach ($header as $k => $v) {
                $header_list[] = sprintf("%s:%s", $k, $v);
            }
            // 设置自定义的 HTTP 请求头。
            // CURLOPT_HTTPHEADER 可以更精细地控制头字段。CURLOPT_USERAGENT 设置的值会被 CURLOPT_HTTPHEADER 中设置的相同头字段的值覆盖
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header_list);
        }

        //设置cookie。适用于需要维护会话或登录状态的场景
        if (!empty($cookie)) {
            $cookie_file = $this->str("{root}/data/cookie/{name}", ["root" => $this->getRootPath(), 'name' => md5($cookie['name'])]);
            $this->writeFile(null, $cookie_file, "w+");
            // 指定一个文件名，cURL 会将服务器响应中的 Set-Cookie 头信息中的 cookie 保存到该文件中
            curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file);
            // 指定一个包含 cookie 信息的文件，cURL 会在发起请求时从这个文件中读取 cookie 并将其发送到服务器
            curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
        }

        // 自动转到重定向之后的新地址，直到请求成功完成
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        // 要访问的地址
        curl_setopt($curl, CURLOPT_URL, $url);
        // 超时时间。表示如果请求在 $timeout 秒内没有完成，cURL 将停止并返回一个错误
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        // 不验证服务器证书的颁发机构（CA）
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // 不检查服务器证书中的主机名是否匹配请求的主机名
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        // curl_exec() 将返回请求的结果作为字符串，赋值给变量，而不是直接输出到标准输出
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // 执行 cURL 会话。发送请求，并等待响应
        $response = curl_exec($curl);

        // 出错
        if ($response === false) {
            // 错误信息
            $ret = curl_error($curl);
        } else {
            // 将 JSON 字符串转换为 JSON 对象
            $ret = json_decode($response, true);
            // 如果 JSON 字符串无效，则直接返回原始数据
            if (empty($ret)) {
                $ret = $response;
            }
        }
        // 关闭 cURL 会话。它会释放由 $curl 句柄所关联的所有资源，包括网络连接、内存等。
        curl_close($curl);
        return $ret;
    }

    /**
     * 伪装成百度蜘蛛，发起HTTP请求
     * 可以完整模拟百度蜘蛛爬取网页时的请求结构
     * 注意：REMOTE_ADDR、REMOTE_PORT是服务端自动获取的，无法通过客户端直接修改，但是，可以通过代理的方式间接修改
     *
     * @param String $url 请求地址
     * @param String $proxy 代理服务器地址和端口（需要在服务器部署代理服务)。http://proxy.example.com:3128
     * @param String $proxy_user_pwd 代理服务器的用户名和密码（验证权限）。username:password
     * @return stdClass response:网页源代码  status:状态码
     */
    public function httpRequestBySpider($url, $proxy = null, $proxy_user_pwd = null)
    {
        // 伪装ua。百度蜘蛛使用的ua
        $user_agent = 'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)';
        // 请求的超时时间（秒）
        $timeout = 8;
        // 百度蜘蛛发起请求时，会携带HTTP请求头参数：HTTP_ACCEPT_ENCODING, HTTP_ACCEPT_LANGUAGE, HTTP_CONNECTION。请求日志显示的顺序正好数组顺序相反
        $http_header = ["Connection: close", "User-Agent:{$user_agent}", "Accept-Language: zh-cn,zh-tw", "Accept:*/*", "Accept-Encoding: gzip"];
        $is_https = substr($url, 0, 8) == 'https://' ? true : false;
        $curl = curl_init();
        // 要访问的地址
        curl_setopt($curl, CURLOPT_URL, $url);
        // 代理服务器
        if ($proxy) {
            // 设置代理服务器地址和端口
            curl_setopt($curl, CURLOPT_PROXY, $proxy);
            if ($proxy_user_pwd) {
                // 设置代理服务器的用户名和密码
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxy_user_pwd);
            }
        }
        // 不包含头部信息
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
        // CURLOPT_HTTPHEADER 可以更精细地控制头字段。CURLOPT_USERAGENT 设置的值会被 CURLOPT_HTTPHEADER 中设置的相同头字段的值覆盖
        curl_setopt($curl, CURLOPT_HTTPHEADER, $http_header);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        // 告诉cURL自动解码gzip压缩的响应
        curl_setopt($curl, CURLOPT_ENCODING, "gzip");
        // 允许重定向
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        // 将响应作为字符串返回，而不是直接输出
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if ($is_https) {
            // 验证对等方的证书是否包含有效的 CN 或 SAN 字段
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            // 不会验证对等方的证书是否由受信任的 CA 签发
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        $response = curl_exec($curl);
        if ($response === false) {
            // 错误信息
            $response = curl_error($curl);
        }
        // 获取 HTTP 状态码
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $obj = new stdClass();
        $obj->url = $url;
        $obj->status = $httpCode;
        $obj->response = $response;
        return $obj;
    }

    /**
     * 获取页面状态
     * 可判断远程文件是否存在(如果网站做过404处理，就检测不出来)
     *
     * @param String $url 地址
     * @return Bool false 页面不存在 true 页面存在
     */
    public function getHtmlStatus($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 在 cURL 请求中不包括响应体（即不包括实际的页面内容）
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        // 如果HTTP请求的结果是一个错误状态码（4xx或5xx），curl_exec 将返回 false
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        // 将 cURL 获取的内容作为字符串返回，而不是直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $status = curl_exec($ch);
        if ($status !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取页面html
     * @param String $url 地址
     **/
    public function getHtmlByFile($url)
    {
        $arrContextOptions = array(
            // 跳过https验证
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
                "allow_self_signed" => true,
            )
        );

        $html = file_get_contents($url, 0, stream_context_create($arrContextOptions));
        return $html;
    }

    /**
     * 下载文件，隐藏真实下载地址
     * 下载路径显示的是下载页面的url
     * 处在同步调用下，方能生效
     * @param {Object} $fileSrc 路径
     * @param {Object} $filename 文件名
     * @param {Object} $mimetype 文件格式
     */
    public function downloadDocument($fileSrc, $filename, $mimetype = "application/octet-stream")
    {
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Disposition: attachment; filename = {$filename}");
        header("Content-Length: " . filesize($fileSrc));
        header("Content-Type: {$mimetype}");
        die(file_get_contents($fileSrc));
    }

    /**
     * 将查询字符串中的参数解析为变量
     * eg:user_id=391&type=ueditor
     * @param {Object} $str
     * @param {Object} $sys 1 内置算法 0 自定义算法
     */
    public function getPara($str, $sys = true)
    {
        if ($sys) {
            parse_str($str, $ret);
            return $ret;
        } else {
            $str = explode("&", $str);
            foreach ($str as $i) {
                $i = explode("=", $i);
                $ret[$i[0]] = $i[1];
            }
            return $ret ?? [];
        }
    }

    /**
     * 在字符串中查找指定字符串
     *
     * eg:findstr('Hello, world!','hello')
     * @param {Object} $str 原始字符串
     * @param {Object} $target  被查找的字符串
     * @return bool true 找到 false 未找到
     */
    public function findStr($str, $target)
    {
        if (strpos($str, $target) !== false) {
            return true;
        }

        return false;
    }

    /**
     * 去掉空格和回车
     * @param {Object} $str
     */
    public function delSpace($str)
    {
        $str = trim($str);
        $str = ltrim($str) . "\n";
        $str = ltrim($str, " ");
        return $str;
    }

    /**
     * 数组转url参数
     * @param {Object} $data
     * @param {Object} $encode 开启URL编码
     */
    public function arr2url($data, $encode = true)
    {
        if (!is_array($data)) {
            return $data;
        }
        if ($encode) {
            return http_build_query($data);
        } else {
            $params = [];
            foreach ($data as $k => $v) {
                $params[] = sprintf("%s=%s", $k, $v);
            }
            return implode('&', $params);
        }
    }

    /**
     *
     * 预定义字符转特殊字符
     * `"`被转成`&quot;`
     * or
     * 特殊字符转预定义字符
     *
     */
    public function html($str, $encode = true)
    {
        if ($encode) {
            return htmlspecialchars($str, ENT_IGNORE);
        } else {
            return htmlspecialchars_decode($str);
        }
    }

    /**
     * 获取字符串中的所有中文
     * @param {Object} $str
     */
    public function getChinese($str)
    {
        //utf-8页面
        preg_match_all("/[\x{4e00}-\x{9fa5}]+/u", $str, $chinese);
        $chinese = implode("", $chinese[0]);

        return $chinese;
    }

    /**
     *
     * 获取html中body标签的内容
     */
    public function getEle($html)
    {
        preg_match("/<body[^>]*?>(.*\s*?)<\/body>/is", $html, $str);

        return $str[0];
    }

    /**
     * 将字符串转换成二进制
     * @param type $str
     * @return type
     *
     */
    public function strToBin($str)
    {
        //1.列出每个字符
        $arr = preg_split('/(?<!^)(?!$)/u', $str);
        //2.unpack字符
        foreach ($arr as &$v) {
            $temp = unpack('H*', $v);
            $v = base_convert($temp[1], 16, 2);
            unset($temp);
        }

        return join(' ', $arr);
    }

    /**
     * 二进制转换成字符串
     * @param type $str
     * @return type
     *
     */
    public function binToStr($str)
    {
        $arr = explode(' ', $str);
        foreach ($arr as &$v) {
            $v = pack("H" . strlen(base_convert($v, 2, 16)), base_convert($v, 2, 16));
        }

        return join('', $arr);
    }

    /**
     * 字符串转十六进制函数
     * @pream string $str='abc';
     *
     */
    public function strToHex($str)
    {
        $hex = "";
        for ($i = 0; $i < strlen($str); $i++) {
            $hex .= dechex(ord($str[$i]));
        }
        $hex = strtoupper($hex);
        return $hex;
    }

    /**
     * 十六进制转字符串函数
     * @pream string $hex='616263';
     *
     */
    public function hexToStr($hex)
    {
        $str = "";
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $str .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        }
        return $str;
    }

    /**
     *
     * 字符串格式化
     * eg:
     * echo format("ddddd:v1cc:v2bb:v2bbccc:v1",array('v1'=>123,'v2'=>555));
     *
     */
    public function format($str, $arr)
    {
        foreach ($arr as $key => $v) {
            //兼容wq
            $key = str_replace(':', '', $key);
            $str = preg_replace("/:{$key}/", is_string($v) ? "'{$v}'" : $v, $str);
        }
        return $str;
    }

    /**
     * php调用网页头的验证功能
     * @param {Object} $account
     * @param {Object} $password
     */
    public function webAuthenticate($account, $password)
    {
        $strAuthUser = $_SERVER['PHP_AUTH_USER'];
        $strAuthPass = $_SERVER['PHP_AUTH_PW'];

        //验证成功
        if ($strAuthUser == $account && $strAuthPass == $password) {
            return true;
        } //验证失败
        else {
            header('WWW-Authenticate: Basic realm="Df"');
            header('HTTP/1.0 401 Unauthorized');
            echo '登录失败';
            return false;
        }
    }

    /**
     * 设置网页状态
     * @param {Object} $var 变量
     **/
    public function setHttpStatus($var = Constants::OK)
    {
        http_response_code($var);
        switch ($var) {
            case Constants::MOVED_PERMANENTLY:
                die("永久重定向");
            case Constants::UNAUTHORIZED:
                die("未经授权");
            case Constants::FORBIDDEN:
                die("禁止访问");
            case Constants::NOT_FOUND:
                die("页面没找到");
            case Constants::OK:
            default:
                break;
        }
    }

    /**
     *
     * 判断是否手机端访问
     */
    public function isMobile()
    {
        //强制调用手机端
        if (isset($_GET['wap'])) {
            return true;
        }

        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset($_SERVER['HTTP_VIA'])) {
            // 找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        // 脑残法，判断手机发送的客户端标志,兼容性有待提高。其中'MicroMessenger'是电脑微信
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile', 'MicroMessenger');
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        // 协议法，因为有可能不准确，放到最后判断
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }
        }
        return false;
    }

    /**
     * 截取指定两个字符之间的字符串
     */
    public function strCut($begin, $end, $str)
    {
        $b = mb_strpos($str, $begin) + mb_strlen($begin);
        $e = mb_strpos($str, $end) - $b;
        return mb_substr($str, $b, $e);
    }

    /**
     * 获取浏览器内核信息
     */
    public function getBrowser()
    {
        return sprintf("%s-%s", $this->getBrowserName(), $this->getBrowserVer());
    }

    /**
     * 获取浏览器内核名称
     */
    public function getBrowserName()
    {
        $agent = $_SERVER["HTTP_USER_AGENT"];
        if (strpos($agent, 'MSIE') !== false || strpos($agent, 'rv:11.0')) { //ie11判断
            return "ie";
        } elseif (strpos($agent, 'Firefox') !== false) { //火狐
            return "firefox";
        } elseif (strpos($agent, 'Chrome') !== false) { //谷歌
            return "chrome";
        } elseif (strpos($agent, 'Opera') !== false) { //opera
            return 'opera';
        } elseif ((strpos($agent, 'Chrome') == false) && strpos($agent, 'Safari') !== false) {
            return 'safari';
        }
    }

    /**
     * 获取浏览器内核版本
     */
    public function getBrowserVer()
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) { //当浏览器没有发送访问者的信息的时候
            return 'unknow';
        }
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/MSIE\s(\d+)\..*/i', $agent, $regs)) { //IE浏览器版本号
            return $regs[1];
        } elseif (preg_match('/FireFox\/(\d+)\..*/i', $agent, $regs)) { //火狐浏览器版本号
            return $regs[1];
        } elseif (preg_match('/Opera[\s|\/](\d+)\..*/i', $agent, $regs)) { //opera浏览器版本号
            return $regs[1];
        } elseif (preg_match('/Chrome\/(\d+)\..*/i', $agent, $regs)) { //谷歌浏览器版本号
            return $regs[1];
        } elseif ((strpos($agent, 'Chrome') == false) && preg_match('/Safari\/(\d+)\..*$/i', $agent, $regs)) {
            return $regs[1];
        } else {
            return 'unknow';
        }
    }

    /**
     * 将一个较大的字节数转换为一个更易读和理解的格式，并返回这个格式的字符串表示
     * @param {Object} $input   字节数
     * @param {Object} $prec    保留小数位数
     */
    public function byteFormat($input, $prec = 0)
    {
        // 标准的字节单位前缀
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        $value = $input;
        $i = 0;

        while ($value > 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, $prec) . ' ' . $units[$i];
    }

    /**
     * 数组中所有元素都是数组则返回true
     * @param {Object} $array
     */
    public function isArray($array)
    {
        if (is_array($array)) {
            foreach ($array as $k => $v) {
                return is_array($v);
            }
        }
        return false;
    }

    /**
     * 获取某年某月最后一天的日期
     * @param {Object} $year
     * @param {Object} $month
     */
    public function getLastDay($year, $month)
    {
        return date('t', strtotime("{$year}-{$month} -1"));
    }

    /**
     * 获取上个月的日期
     * @param {Object} $var 变量
     **/
    public function getLastMonth($year, $month)
    {
        // 使用strtotime获取上一个月的日期
        $previousMonthDate = strtotime('-1 month', strtotime("{$year}-{$month}"));
        // 使用date格式化上个月的年月
        $previousYear = date('Y', $previousMonthDate);
        $previousMonth = date('m', $previousMonthDate);
        $obj = new stdClass();
        $obj->year = $previousYear;
        $obj->month = $previousMonth;
        return $obj;
    }

    /**
     * 判断是否是时间戳
     * @param {Object} $timestamp
     */
    public function isTimestamp($timestamp)
    {
        if (strtotime(date('Y-m-d H:i:s', $timestamp)) === intval($timestamp)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * base64加密
     * @param {Object} $obj
     */
    public function b64Encode($obj)
    {
        if (is_array($obj)) {
            return urlencode(base64_encode(json_encode($obj)));
        }

        return urlencode(base64_encode($obj));
    }

    /**
     * base64解密
     * @param {Object} $str
     * @param {Object} $is_array
     */
    public function b64Decode($str, $is_array = true)
    {
        $str = base64_decode(urldecode($str));

        if ($is_array) {
            return json_decode($str, true);
        }

        return $str;
    }

    /**
     * 将数组中的元素进行html原样输出
     * @param {Object} $var
     */
    public function iHtmlspecialchars($var)
    {
        if (is_array($var)) {
            foreach ($var as $key => $value) {
                $var[htmlspecialchars($key)] = $this->ihtmlspecialchars($value);
            }
        } else {
            $var = str_replace('&amp;', '&', htmlspecialchars($var, ENT_QUOTES));
        }
        return $var;
    }

    /**
     * 获取当前页面的完整网址
     * @param {Object} $getPort
     */
    public function getCurUrl($getPort = 0)
    {
        if (!$getPort) {
            return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            return 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        }
        //包含端口号的完整url
    }

    /**
     * 执行控制台命令
     * @param {Object} $shell
     */
    public function runShell($shell)
    {
        //     echo exec('whoami');
        echo exec($shell);
    }

    /**
     * 获取"/"后的字符串
     * @param {Object} $file_name
     */
    public function getLast($file_name)
    {
        //获取数组最后一条数据
        //用／号对字符串进行分组
        $a = explode('/', $file_name);
        return array_pop($a);
    }

    /**
     * 分割字符串
     * eg:split("1|2|3","|");
     * @param {Object} $str
     * @param {Object} $char
     */
    public function split($str, $char)
    {
        $rt = explode($char, $str);
        return $rt;
    }

    /**
     * 生成一个指定大小的数组
     * @param {Object} $a
     */
    public function defineArr($a)
    {
        $array = array();
        for ($i = 0; $i < $a; $i++) {
            $array[$i] = $i;
        }
        return $array;
    }

    /**
     * 对象转bool
     **/
    public function objToBool($obj)
    {
        if (is_numeric($obj)) {
            return intval($obj) > 0;
        } elseif (is_string($obj)) {
            return strtolower($obj) == 'true';
        } else {
            return boolval($obj);
        }
    }

    /**
     * @Description: 将时间转换为几秒前、几分钟前、几小时前、几天前
     * @param $the_time 需要转换的时间。时间戳或者时间字符串
     * @return string
     */
    public function timeTran($the_time)
    {
        $now_time = date("Y-m-d H:i:s", time());
        $now_time = strtotime($now_time);
        $show_time = is_numeric($the_time) ? $the_time : strtotime($the_time);
        $dur = $now_time - $show_time;
        if ($dur < 0) {
            return $the_time;
        } else {
            if ($dur < 60) {
                return $dur . '秒前';
            } else {
                if ($dur < 3600) {
                    return floor($dur / 60) . '分钟前';
                } else {
                    if ($dur < 86400) {
                        return floor($dur / 3600) . '小时前';
                    } else {
                        return floor($dur / 86400) . '天前';
                    }
                }
            }
        }
    }

    /**
     * 将距今相隔的时间转换为秒、分钟、小时、天
     * @param $beginDate  开始日期。时间戳或者时间字符串
     * @return string
     */
    public function timeCalculation($begin_time)
    {
        $begin_time = is_numeric($begin_time) ? $begin_time : strtotime($begin_time);
        $subTime = time() - $begin_time;
        $day = $subTime > 86400 ? floor($subTime / 86400) : 0;
        $subTime -= $day * 86400;
        $hour = $subTime > 3600 ? floor($subTime / 3600) : 0;
        $subTime -= $hour * 3600;
        $minute = $subTime > 60 ? floor($subTime / 60) : 0;
        $subTime -= $minute * 60;
        $second = $subTime;

        $dayText = $day ? $day . '天' : '';
        $hourText = $hour ? $hour . '小时' : '';
        $minuteText = $minute ? $minute . '分钟' : '';
        $secondText = $second ? $second . '秒' : '';
        $date = $dayText . $hourText . $minuteText . $second;
        return $dayText;
    }

    /**
     * 冒泡排序
     * 默认：从大到小
     * 数值相同，则原始数组前方的靠前
     * @param {Object} array $arr    原始数组
     * @param {Object} $key_name    某个key
     * @param {Object} $is_asc    排序方式。true 升序 false 降序
     */
    public function bubbleSort(array $arr, $key_name, $is_asc = false)
    {
        for ($i = 0; $i < count($arr); $i++) {
            $data = '';
            for ($j = $i + 1; $j < count($arr); $j++) {
                if ($arr[$i][$key_name] < $arr[$j][$key_name]) {
                    $data = $arr[$i];
                    $arr[$i] = $arr[$j];
                    $arr[$j] = $data;
                }
            }
        }
        $arr = $is_asc ? array_reverse($arr, false) : $arr;
        return $arr;
    }

    /**
     * 对数组的某个值降序排列，并根据顺序添加一个排序字段
     * @param {Object} array $arr    原始数组
     * @param {Object} $key_name    某个key
     */
    public function setRankingByDesc(array $arr, $key_name)
    {
        $list = $this->bubbleSort($arr, $key_name);
        foreach ($list as $key => &$value) {
            $value["{$key_name}_ranking"] = $key + 1;
        }
        return $list;
    }

    /**
     * 根据奖品概率，进行随机抽奖，返回中奖的编号
     * 每行数据的值必须大于等于0，相对数值越大则中奖概率越高，反之则概率越小
     * 所有数值的总和不受限制，根据每条数据相对于总和的概率依次进行筛选
     * 原理：将所有值进行累加，用单个值占总值的比例作为该项的概率，该值占总值的比例越大则概率越高，反之越小
     * @param {Object} $proArr
     */
    public function getPrize($proArr)
    {
        $result = '';
        //概率数组的总概率精度。数组值的总和
        $proSum = array_sum($proArr);
        if ($proSum == 0) {
            return null;
        }

        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset($proArr);
        return $result;
    }

    /**
     * 字符串半角和全角间相互转换
     * 半角即英文字符，全角即中文字符
     * @param string $str 待转换的字符串
     * @param int $type 类型。Constants::TO_DBC 转换为全角  Constants::TO_SBC 转换为半角
     * @return string 返回转换后的字符串
     */
    public function convertStrType($str, $type)
    {
        // 全角（中文字符）
        $dbc = array(
            '０',
            '１',
            '２',
            '３',
            '４',
            '５',
            '６',
            '７',
            '８',
            '９',
            'Ａ',
            'Ｂ',
            'Ｃ',
            'Ｄ',
            'Ｅ',
            'Ｆ',
            'Ｇ',
            'Ｈ',
            'Ｉ',
            'Ｊ',
            'Ｋ',
            'Ｌ',
            'Ｍ',
            'Ｎ',
            'Ｏ',
            'Ｐ',
            'Ｑ',
            'Ｒ',
            'Ｓ',
            'Ｔ',
            'Ｕ',
            'Ｖ',
            'Ｗ',
            'Ｘ',
            'Ｙ',
            'Ｚ',
            'ａ',
            'ｂ',
            'ｃ',
            'ｄ',
            'ｅ',
            'ｆ',
            'ｇ',
            'ｈ',
            'ｉ',
            'ｊ',
            'ｋ',
            'ｌ',
            'ｍ',
            'ｎ',
            'ｏ',
            'ｐ',
            'ｑ',
            'ｒ',
            'ｓ',
            'ｔ',
            'ｕ',
            'ｖ',
            'ｗ',
            'ｘ',
            'ｙ',
            'ｚ',
            '－',
            '　',
            '：',
            '．',
            '，',
            '／',
            '％',
            '＃',
            '！',
            '＠',
            '＆',
            '（',
            '）',
            '＜',
            '＞',
            '＂',
            '＇',
            '？',
            '［',
            '］',
            '｛',
            '｝',
            '＼',
            '｜',
            '＋',
            '＝',
            '＿',
            '＾',
            '￥',
            '￣',
            '｀'
        );
        // 半角（英文字符）
        $sbc = array(
            '0',
            '1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'W',
            'X',
            'Y',
            'Z',
            'a',
            'b',
            'c',
            'd',
            'e',
            'f',
            'g',
            'h',
            'i',
            'j',
            'k',
            'l',
            'm',
            'n',
            'o',
            'p',
            'q',
            'r',
            's',
            't',
            'u',
            'v',
            'w',
            'x',
            'y',
            'z',
            '-',
            ' ',
            ':',
            '.',
            ',',
            '/',
            '%',
            ' #',
            '!',
            '@',
            '&',
            '(',
            ')',
            '<',
            '>',
            '"',
            '\'',
            '?',
            '[',
            ']',
            '{',
            '}',
            '\\',
            '|',
            '+',
            '=',
            '_',
            '^',
            '￥',
            '~',
            '`'
        );

        if ($type == Constants::TO_DBC) {
            //半角到全角
            return str_replace($sbc, $dbc, $str);
        } elseif ($type == Constants::TO_SBC) {
            //全角到半角
            return str_replace($dbc, $sbc, $str);
        } else {
            return $str;
        }
    }

    /**
     * 去掉空格，回车，换行，tab
     */
    public function trimAll($str)
    {
        $oldchar = array(" ", "　", "\t", "\n", "\r");
        $newchar = array("", "", "", "", "");
        return str_replace($oldchar, $newchar, $str);
    }

    /**
     * 判断空字符串或者null
     * 不包括零
     */
    public function isEmpty($obj)
    {
        if (!isset($obj) || $obj === null || $this->trimAll($obj) === '') {
            return true;
        }
        return false;
    }

    /**
     * 二维数组通过key去重
     * @param {Object} $array
     * @param {Object} $key
     */
    public function uniqueByKey($array, $key)
    {
        $temp_array = [];
        $i = 0;
        $key_array = [];
        foreach ($array as $val) {
            if (!in_array(trim($val[$key]), $key_array)) {
                $key_array[$i] = trim($val[$key]);
                $temp_array[$i] = $val;
            }
            $i++;
        }
        return $temp_array;
    }

    /**
     * 获取数组中指定项
     * @param {Object} $array 原始数组 ['北京'=>[1,2,3]]
     * @param {Object} $item 栏目 ['北京','上海']
     **/
    public function getArrayItem($array = [], $item = [])
    {
        $new_arr = [];
        foreach ($item as $key => $value) {
            $new_arr[$value] = $array[$value] ?? null;
        }
        return $new_arr;
    }

    /**
     * 运行脚本
     * @param {Object} $var 脚本
     **/
    public function runScript($var = null)
    {
        return $this->str(shell_exec($var));
    }

    /**
     * 下划线转驼峰
     * 思路:
     * step1.原字符串转小写,原字符串中的分隔符用空格替换,在字符串开头加上分隔符
     * step2.将字符串中每个单词的首字母转换为大写,再去空格,去字符串首部附加的分隔符.
     */
    public function hump($uncamelized_words, $separator = '_')
    {
        $words = str_replace($separator, " ", strtolower($uncamelized_words));
        return str_replace(" ", "", ucwords($words));
    }

    /**
     * 驼峰命名转下划线命名
     * 思路:
     * 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
     */
    public function unHump($camelCaps, $separator = '_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }

    /**
     * 格式化字符串
     * 非字符串的数据会自动被转化为字符串
     * eg:
     * str("admin/home/{0}/{dd}",[123,'dd'=>333])
     * str("admin/home/%s/%s",[123,333])
     * @param {Object} $string    字符串
     * @param {Object} $params    参数
     */
    public function str($string, $params = [])
    {
        $string = is_string($string) ? $string : var_export($string, true);

        foreach ($params as $key => $value) {
            if (is_string($key) && !$this->findStr($string, $key))
                continue;
            $value = $this->isEmpty($value) ? "" : $value;
            $string = preg_replace("/\{$key\}/", $value, $string);
            $search = "%s";
            // 在连续调用时会保留上次的查找坐标
            $position = strpos($string, $search);
            if ($position !== false)
                $string = substr_replace($string, $value, $position, 2);
        }
        return $string;
    }

    /**
     * 第一个参数为空就调用第二个参数
     * @param {Object} $default
     * @param {Object} $other
     */
    public function setVal($default, $other)
    {
        $rt = empty($default) ? $other : $default;
        return $rt;
    }

    /**
     * 获取缩略文本
     * @param {Object} $str    原始字符串
     * @param {Object} $length    缩略文本长度
     */
    public function getThumbnailText($str, $length)
    {
        if ($length <= 0) {
            return "";
        }
        // 去掉HTML标签
        $text = strip_tags($str);
        // 截取UTF-8编码的字符串
        $tn_text = mb_substr($text, 0, $length);
        // 获取UTF-8编码的字符串长度
        if (mb_strlen($text) > $length) {
            return $tn_text . '...';
        }
        return $tn_text;
    }

    /**
     * 检查字符串中是否包含某些字符串
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    public function contains($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查字符串是否以某些字符串结尾
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    public function endsWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ((string)$needle === $this->substr($haystack, -$this->length($needle))) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查字符串是否以某些字符串开头
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    public function startsWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取指定长度的随机字母数字组合的字符串
     *
     * @param int $length
     * @return string
     */
    public function random($length = 16)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return $this->substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }

    /**
     * 生成一段特定长度的具有高度唯一性的随机字符串
     * UUIDv4（Universally Unique Identifier version 4）使用随机数或伪随机数来生成大部分的字节，长度为36个字符（4个短横线+32个十六进制数），包括4组由短横线分隔的十六进制数（每组分别为 8、4、4、4、12 个字符），技术上，你可以缩短这个字符串，但这样做会牺牲其作为全局唯一标识符的可靠性。UUIDv4 的长度是基于其设计目标和算法需求来确定的，旨在提供极高的唯一性保证。缩短 UUID 字符串将减少其可用的唯一值数量，从而增加在不同场合下发生冲突的风险。
     *
     * UUIDv4提供了大约2122（约等于5.3×1036）个可能的UUID。这意味着在极端情况下，例如每秒生成10亿个UUID，在接下来的100年内只产生一个重复的概率约为50%。
     * 对于绝大多数应用场景来说，UUIDv4的重复概率可以忽略不计。UUIDv4的设计目标就是在全球范围内为数据对象分配独一无二的标识符，并且其生成算法在多种情况下都能够保持其唯一性。
     * 尽管UUIDv4的重复概率极低，但在某些特定场景下，如分布式系统或高并发环境中，仍然需要采取额外的措施来确保数据的唯一性。例如，可以将UUIDv4与其他唯一性约束（如数据库的自增主键）结合使用。
     */
    public function generateUUIDv4()
    {
        // 按照UUIDv4的标准生成16字节的随机数据
        $data = random_bytes(16);
        assert(strlen($data) == 16);
        // 设置版本号为0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // 设置版本为0100
        // 设置变体为0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // 设置变体为10
        // 输出32个十六进制数字，以短横线分隔成5组
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * 牺牲UUIDv4的高度唯一性，从而获取指定长度的UUIDv4字符串
     * 对UUIDv4进行哈希处理（如使用SHA-256），然后截取哈希值的一部分作为较短的唯一标识符。这种方法可以显著缩短标识符的长度，但也会增加冲突的可能性。
     * @param {Object} $uuidv4
     * @param {Object} $length
     */
    public function generateShortUUID($length = 5)
    {
        $uuidv4 = $this->generateUUIDv4();
        $hash = hash('sha256', $uuidv4);
        return substr($hash, 0, $length);
    }

    /**
     * 字符串转小写
     *
     * @param string $value
     * @return string
     */
    public function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * 字符串转大写
     *
     * @param string $value
     * @return string
     */
    public function upper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * 获取字符串的长度
     *
     * @param string $value
     * @return int
     */
    public function length($value)
    {
        return mb_strlen($value);
    }

    /**
     * 截取字符串
     *
     * @param string $string
     * @param int $start
     * @param int|null $length
     * @return string
     */
    public function substr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    protected static $snakeCache = [];
    protected static $camelCache = [];
    protected static $studlyCache = [];

    /**
     * 驼峰转下划线
     *
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    public function snake($value, $delimiter = '_')
    {
        $key = $value;

        if (isset(static::$snakeCache[$key][$delimiter])) {
            return static::$snakeCache[$key][$delimiter];
        }

        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', $value);

            $value = $this->lower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }

        return static::$snakeCache[$key][$delimiter] = $value;
    }

    /**
     * 下划线转驼峰(首字母小写)
     *
     * @param string $value
     * @return string
     */
    public function camel($value)
    {
        if (isset(static::$camelCache[$value])) {
            return static::$camelCache[$value];
        }

        return static::$camelCache[$value] = lcfirst($this->studly($value));
    }

    /**
     * 下划线转驼峰(首字母大写)
     *
     * @param string $value
     * @return string
     */
    public function studly($value)
    {
        $key = $value;

        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }

        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return static::$studlyCache[$key] = str_replace(' ', '', $value);
    }

    /**
     * 转为首字母大写的标题格式
     *
     * @param string $value
     * @return string
     */
    public function title($value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * 替换字符串
     * 例如：
     *      Common::strReplace($inputHTML,['&nbsp;'],[null])
     *
     * @param {Object} $str 原始字符串
     * @param {Object} $from 被替换的字符串
     * @param {Object} $to 替换之后的字符串
     */
    public function strReplace($str, $from, $to)
    {
        $newString = str_replace($from, $to, $str);
        return $newString;
    }

    /**
     * 将二进制数据转化为支持URL的base64字符串
     * @param {Object} $data 二进制数据
     */
    public function base64UrlEncode($data)
    {
        // 转化base64字符串中的“+/”，去掉末尾的“=”
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * 换行符转化
     * 文件换行符与html换行符相互转化
     * @param {Object} $str    字符串
     * @param {Object} $file2html    true 文件转html false html转文件
     */
    public function newlineConversion($str, $type = Constants::NL_CRLF2BR)
    {
        switch ($type) {
            case Constants::NL_CRLF2BR:
                $ret = str_replace(PHP_EOL, "<br />", $str);
                break;
            case Constants::NL_BR2CRLF:
                // 匹配任何形式的br标签，不区分大小写以及标签中的空格
                $ret = preg_replace('/<br\\s*?\/??>/i', PHP_EOL, $str);

                break;
            default:
                $ret = $str;
                break;
        }
        return $ret;
    }

    /**
     * 执行php代码并捕获异常信息
     *
     * @param {Object} $code 代码
     * eg:
     * $code = <<<'EOT'
     * $data = [1, 2, 3];
     * $result = $data['non_existent_key']; // 这将引发一个错误
     * EOT;
     */
    function executeCodeWithFaultTolerance($code)
    {
        try {
            // 执行传入的代码
            eval($code);
        } catch (Exception $e) {
            // 处理异常
            echo "捕获到异常: " . $e->getMessage() . "\n";
            // 在这里可以进行一些容错处理，比如记录日志、回滚操作等
        } catch (Error $e) {
            // 处理错误
            echo "捕获到错误: " . $e->getMessage() . "\n";
            // 在这里可以进行一些容错处理，比如记录日志、回滚操作等
        }
    }

    /**
     * 获取错误验证信息
     * @param {Exception} $exception 验证对象
     * @return {String} 错误详情
     **/
    public function getException(Exception $exception)
    {
        $trace_list = [];
        $trace_list[] = $this->str("%s %s", [$exception->getFile(), $exception->getLine()]);
        $trace_list[] = "";
        foreach ($exception->getTrace() as $key => $value) {
            if (empty($value['file']))
                continue;
            $trace_list[] = $this->str("%s %s", [$value['file'], $value['line']]);
        }

        $err_msg = $this->str(
            <<<STR

        ////////////////////////////////////////////////// 出错 START //////////////////////////////////////////////////
        {0}
        {1}
        //////////////////////////////////////////////////  出错 END  //////////////////////////////////////////////////

        STR,
            [$exception->getMessage(), implode(PHP_EOL, $trace_list)]
        );

        return $err_msg;
    }

    /**
     * 网页环境下判断当前页面是否本地域名
     * @param {Object} $var 变量
     **/
    public function isLocal($var = null)
    {
        $domain = $_SERVER['HTTP_HOST'];

        if (substr($domain, -6) === '.local') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 通过菜单子级id获取所有的父级id
     * eg:
     *     Common::buildParentTree($portalCategoryModel->field('id,parent_id,name')->select()->toArray(),$categories[0]['id'])
     * @param {Object} $categories
     * @param {Object} $parentId
     */
    public function buildParentTree($categories, $parentId)
    {
        $tree = null;
        foreach ($categories as $category) {
            if ($category['id'] == $parentId) {
                $children = $this->buildParentTree($categories, $category['parent_id']);
                if ($children) {
                    $category['parent'] = $children;
                }
                $tree = $category;
            }
        }
        return $tree;
    }

    /**
     * 递归函数，遍历菜单子级id获取的所有的父级id，用数组表示从小到大的目录结构
     * @param {Object} $array
     * @param {Object} $list
     * @return {Array} 从小到大的目录结构
     */
    public function traverseParentTree($array, $list = [])
    {
        if (isset($array['id']) && isset($array['name'])) {
            $list[$array['id']] = $array['name'];
        }
        if (isset($array['parent']) && is_array($array['parent'])) {
            $list = $this->traverseParentTree($array['parent'], $list);
        }
        // 通过key进行正序排列
        ksort($list);
        return $list;
    }

    /**
     * 通过菜单子级id获取所有的父级id，返回从小到大的目录数组
     * eg:
     *     Common::buildParentTreeMenu($portalCategoryModel->field('id,parent_id,name')->select()->toArray(),$categories[0]['id'])
     * @param {Object} $categories
     * @param {Object} $parentId
     * @return {Array} 从小到大的目录结构 eg:[1=>"菜单一",5=>"菜单一(1)",7=>"菜单一(1)(1)"]
     */
    public function buildParentTreeMenu($categories, $parentId)
    {
        $categories_tree = $this->buildParentTree($categories, $parentId);
        $list = $this->traverseParentTree($categories_tree);
        return $list;
    }

    /**
     * 通过菜单父级id获取所有的子级id
     * eg:
     *     Common::buildChildTree($portalCategoryModel->field('id,parent_id,name')->select()->toArray(),$categories[0]['id'])
     * @param {Object} $categories
     * @param {Object} $childId
     */
    public function buildChildTree($categories, $childId)
    {
        $tree = [];

        foreach ($categories as $category) {
            if ($category['parent_id'] == $childId) {
                $children = $this->buildChildTree($categories, $category['id']);
                if ($children) {
                    $category['children'] = $children;
                }
                $tree[] = $category;
            }
        }

        return $tree;
    }

    /**
     * 从oss获取其余尺寸的图
     * eg:
     *     https://res.tye3.com/kp_tye3/image/2024/04/6nRFsXy6jXEJ63wJ.jpg
     *     https://res.tye3.com/kp_tye3/image/2024/04/m/6nRFsXy6jXEJ63wJ.jpg
     *     https://res.tye3.com/kp_tye3/image/2024/04/s/6nRFsXy6jXEJ63wJ.jpg
     * @param {Object} $file_src    图片路径 eg:https://res.tye3.com/kp_tye3/image/2024/04/6nRFsXy6jXEJ63wJ.jpg
     * @param {Object} $type    类型。Common::OSS_SIZE_NORMAL 原图   Common::OSS_SIZE_MIDDLE 中图  Common::OSS_SIZE_SMALL 小图
     */
    function getOtherSizeFromOss($file_src, $type = Constants::OSS_SIZE_NORMAL)
    {
        // /path/to
        $dirname = pathinfo($file_src, PATHINFO_DIRNAME);
        // file.txt
        $basename = pathinfo($file_src, PATHINFO_BASENAME);
        // txt
        $extension = pathinfo($file_src, PATHINFO_EXTENSION);

        switch ($type) {
            case Constants::OSS_SIZE_NORMAL:
                return $file_src;
                break;
            default:
                return $dirname . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $basename;
                break;
        }
    }

    /**
     * 通过参数从oss获取其余尺寸的图
     * @param {Object} $file_src    图片路径 eg:https://res.tye3.com/kp_tye3/image/2024/04/6nRFsXy6jXEJ63wJ.jpg
     * @param {Object} $param    参数。style/kp_img_s 调用样式名    image/auto-orient,1/resize,m_pad,w_200,h_200 调用样式详情
     */
    function getOtherSizeFromOssByParam($file_src, $param)
    {
        $file_src = "{$file_src}?x-oss-process={$param}";
        return $file_src;
    }

    /**
     * 设置html中所有video的封面
     * @param {Object} $htmlContent html代码。eg:<html><body><video src="http://res.tye3.com/ktp_tye3/16yHwX6GA1fkedAi.mp4"></video></body></html>
     * @param {Object} $defaultCover 默认封面。eg:http://ktp.tye3.com/themes/ktp_v1/public/assets/images/temp/v_poster.jpg
     **/
    public function setHtmlVideoCover($htmlContent = null, $defaultCover = null)
    {
        if (empty($htmlContent)) {
            return $htmlContent;
        }
        // 捕获html的格式错误
        libxml_use_internal_errors(true);
        // 创建一个 DOMDocument 对象
        $dom = new DOMDocument();
        // 加载 HTML 内容。支持中文显示
        $dom->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'));
        // 获取所有错误
        $errors = libxml_get_errors();
        // 清除错误缓存
        libxml_clear_errors();
        // 获取所有的 <video> 标签
        $videos = $dom->getElementsByTagName('video');
        // 遍历所有 <video> 标签
        foreach ($videos as $video) {
            // 获取 src 属性
            $src = $video->getAttribute('src');
            // /path/to
            $dirname = pathinfo($src, PATHINFO_DIRNAME);
            // file
            $filename = pathinfo($src, PATHINFO_FILENAME);

            // 构造 poster 的 URL（这里假设 poster 与视频文件在同一个目录下，并且文件名为 src 的同名图片，后缀为 .jpg）
            $poster = $dirname . DIRECTORY_SEPARATOR . "c" . DIRECTORY_SEPARATOR . $filename . '.jpg'; // 假设视频是 .mp4 格式
            if (!$this->getHtmlStatus($poster)) {
                $poster = $defaultCover;
            }
            // 添加 poster 属性到 <video> 标签
            $video->setAttribute('poster', $poster);
        }
        // 保存修改后的 HTML。自动添加html和body标签
        // $newHtml = $dom->saveHTML();
        // 获取 body 元素
        $body = $dom->getElementsByTagName('body')->item(0);
        // 获取body元素内的所有子节点的HTML内容
        $bodyHtml = [];
        foreach ($body->childNodes as $childNode) {
            $bodyHtml[] = $dom->saveHTML($childNode);
        }
        $newHtml = implode("", $bodyHtml);
        // 输出修改后的 HTML
        return $newHtml;
    }

    /**
     * 规范目录分隔符
     * @param {Object} $var 变量
     **/
    public function formatDirectorySeparator($var = null)
    {
        $var = str_replace(['/', '\\'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $var);
        return $var;
    }

    /**
     * 在命令行输出带颜色的字符串
     * @param {Object} $textColor ANSI转义序列文字颜色。30=黑色, 31=红色, 32=绿色, 33=黄色, 34=蓝色, 35=洋红（紫色）, 36=青色（深绿）, 37=白色, 90=明亮的黑色, 91=明亮的红色, 92=明亮的绿色, 93=明亮的黄色, 94=明亮的蓝色, 95=明亮的洋红（紫色）, 96=明亮的青色（深绿）, 97=明亮的白色
     * @param {Object} $bgColor ANSI转义序列文字颜色。40=黑色背景, 41=红色背景, 42=绿色背景, 43=黄色背景, 44=蓝色背景, 45=洋红（紫色）背景, 46=青色（深绿）背景, 47=白色背景,100=明亮的黑色背景, 101=明亮的红色背景, 102=明亮的绿色背景, 103=明亮的黄色背景, 104=明亮的蓝色背景, 105=明亮的洋红（紫色）背景, 106=明亮的青色（深绿）背景, 107=明亮的白色背景
     **/
    public function colorEcho($message, $textColor = 37, $bgColor = 45)
    {
        // 构建ANSI转义序列
        $startColor = "\033[" . $bgColor . ";" . $textColor . "m";
        // 重置颜色到默认
        $resetColor = "\033[0m";
        // 输出带有颜色和背景颜色的字符串
        echo $startColor . $message . $resetColor;
    }

    /**
     * 获取最后一个斜杠后的字符串
     * @param {Object} $str
     */
    public function getLastSlashStr($str)
    {
        $arr = explode("\\", $str);
        return $arr[count($arr) - 1];
    }

    /**
     * 是否为windows环境
     * @param {Object} $var 变量
     **/
    public function isWindows($var = null)
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return true;
        } else
            return false;
    }

    /**
     * 去掉数组中最小的值
     * @param {Object} $array 数组
     **/
    public function removeMinValue($list)
    {
        $origin_list = $list;
        if (empty($list)) {
            return $list;
        }

        $minValue = min($list);
        $key = array_search($minValue, $list);
        if ($key !== false) {
            unset($list[$key]);
        }

        $obj = new class($minValue, $origin_list, $list) {
            public $remove_value, $origin_list, $list;

            public function __construct($remove_value, $origin_list, $list)
            {
                $this->remove_value = $remove_value;
                $this->origin_list = $origin_list;
                $this->list = $list;
            }
        };
        return $obj;
    }

    /**
     * 去掉 URL 中的所有参数，只保留基本 URL 路径（即域名和路径部分）
     * @param {Object} $url 地址
     */
    public function removeQueryParams($url)
    {
        $parts = parse_url($url);

        // 移除查询字符串和片段
        $parts['query'] = null;
        $parts['fragment'] = null;

        // 重新构建 URL（不包含查询字符串和片段）
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = isset($parts['host']) ? $parts['host'] : '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = isset($parts['path']) ? $parts['path'] : '';

        $newUrl = $scheme . $host . $port . $path;

        return $newUrl;
    }

    /**
     * 根据文件扩展名来获取对应的 MIME 类型
     * @param object $fileType 文件扩展名
     * @return mixed
     **/
    public function getMimeType($fileType = null)
    {
        $mimeTypes = [
            // ********************** 文档 START **********************
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'xml' => 'application/xml',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'slk' => 'application/vnd.ms-excel', // 假设与 XLS 相同
            'gnumeric' => 'application/x-gnumeric',
            'html' => 'text/html',
            'csv' => 'text/csv',
            // **********************  文档 END  **********************

            // ********************** 图片 START **********************
            'gif' => 'image/gif',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'ico' => 'image/vnd.microsoft.icon',
            // **********************  图片 END  **********************

            // ********************** 音频 START **********************
            'mp3' => 'audio/mp3',
            'mpeg' => 'audio/mpeg',
            // **********************  音频 END  **********************

            // ********************** 视频 START **********************
            'mp4' => 'video/mp4',
            // **********************  视频 END  **********************

            // ********************** 应用程序 START **********************
            'zip' => 'application/zip',
            'apk' => 'application/vnd.android.package-archive'
            // **********************  应用程序 END  **********************
        ];

        if ($fileType === null) {
            return $mimeTypes;
        }

        // 将文件类型转换为小写，以便进行不区分大小写的比较
        $fileType = strtolower($fileType);

        // 如果找到了对应的 MIME 类型，则返回它
        if (array_key_exists($fileType, $mimeTypes)) {
            return $mimeTypes[$fileType];
        } else {
            // 如果没有找到对应的 MIME 类型，则返回一个默认值或抛出异常
            return 'application/octet-stream'; // 默认二进制流数据
        }
    }

    /**
     * 获取MIME_TYPE前缀
     * @param {Object} $mimeType
     */
    function getMimeTypePrefix($mimeType)
    {
        $slashPos = strpos($mimeType, '/');
        if ($slashPos !== false) {
            return substr($mimeType, 0, $slashPos);
        }
        // 如果没有斜杠，返回完整的 MIME 类型
        return $mimeType;
    }

    /**
     * 链接跳转
     */
    public function toUrl($host)
    {
        die(header("Location:{$host}"));
    }

    /**
     * 获取代码运行时长和内存占用量
     * 计算代码运行期间相差的时间戳（微秒）和相差的内存使用量。一段代码运行期间，时间戳、内存使用量必然是不变或者增加的
     * 使用方法:
     * Common::codePerformance('begin'); // 记录开始标记位
     * // ... 区间运行代码
     * Common::codePerformance('end'); // 记录结束标签位
     * echo Common::codePerformance('begin','end',6); // 统计区间运行时间 精确到小数后6位
     * echo Common::codePerformance('begin','end','m'); // 统计区间内存使用情况
     * @param string $start 开始标签
     * @param string $end 结束标签
     * @param integer|string $dec 小数位:时间戳保留的小数位数 m:获取内存使用量
     * @return mixed
     */
    public function codePerformance($start, $end = '', $dec = 4)
    {
        $memory_limit_on = function_exists('memory_get_usage');
        static $_time       =   array();
        static $_mem        =   array();
        if (empty($end)) {
            // 记录时间和内存使用
            $_time[$start]  =  microtime(TRUE);
            $_mem[$start] = $memory_limit_on ? memory_get_usage() : null;
        } else {
            // 统计时间和内存使用
            $_time[$start] = $_time[$start] ?? microtime(TRUE);
            $_time[$end] = $_time[$end] ?? microtime(TRUE);
            if ($memory_limit_on && $dec == 'm') {
                $_mem[$end] = $_mem[$end] ??  memory_get_usage();
                // 将数字格式化为带有千位分隔符和小数点的字符串
                return number_format(($_mem[$end] - $_mem[$start]) / 1024);
            } else {
                // 将数字格式化为带有千位分隔符和小数点的字符串
                return number_format(($_time[$end] - $_time[$start]), $dec);
            }
        }
        return null;
    }

    /**
     * 关联数组根据值（value）来查找对应的键（key）
     * @param {Object} $array
     * @param {Object} $value
     */
    public function getKeyByValue($array, $value)
    {
        foreach ($array as $key => $val) {
            if ($val === $value) {
                return $key;
            }
        }
        return null; // 如果没有找到对应的值，返回 null
    }
}
