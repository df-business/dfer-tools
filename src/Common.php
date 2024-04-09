<?php

namespace Dfer\Tools;

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
 *                 ......    .!%$! ..        | AUTHOR: dfer
 *         ......        .;o*%*!  .          | EMAIL: df_business@qq.com
 *                .:;;o&***o;.   .           | QQ: 3504725309
 *        .;;!o&****&&o;:.    ..
 * +----------------------------------------------------------------------
 *
 */
class Common
{
    // 静态属性，保存单例实例  
    private static $instance;

    use ImgTrait, FilesTrait;

    const TIME_FULL = 0, TIME_YMD = 1;

    const REQ_JSON = 0, REQ_GET = 1, REQ_POST = 2;

    const OK = 200, MOVED_PERMANENTLY = 301, UNAUTHORIZED = 401, FORBIDDEN = 403, NOT_FOUND = 404;

    //um单个文件上传;um编辑框;layui编辑器上传;editormd编辑器上传;baidu组件上传
    const UPLOAD_UMEDITOR_SINGLE = 0, UPLOAD_UMEDITOR_EDITOR = 1, UPLOAD_LAYUI_EDITOR = 2, UPLOAD_EDITORMD_EDITOR = 3, UPLOAD_WEB_UPLOADER = 4;

    const NL_CRLF2BR = 0, NL_BR2CRLF = 1;

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
     */
    public function showJson($status = 1, $data = array(), $success_msg = '', $fail_msg = '')
    {
        $msg = boolval($status) ? ($success_msg ?: '操作成功') : ($fail_msg ?: '操作失败');

        $ret = array(
            'status' => $status,
            'msg' => $msg
        );
        if ($data) {
            $ret['data'] = $data;
        }

        self::showJsonBase($ret);
    }

    /**
     *	输出json数据
     * @param {Object} $return	数据
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
     * getTime(1709091401,"Y/m/d H:i:s")
     * getTime("2024-02-28 11:36:41","Y/m/d H:i:s")
     * getTime(null,"Y/m/d H:i:s")
     * @param {Object} $time 时间数据。int 时间戳(1709091401) string 时间字符串(2024-02-28 11:36:41)
     * @param {Object} $type 类型
     * @return {Object} 正常时间格式(2024-02-28 11:36:41)
     */
    public function getTime($time = null, $type = self::TIME_FULL)
    {
        switch ($type) {
            case self::TIME_FULL:
                $format = 'Y-m-d H:i:s';
                break;
            case self::TIME_YMD:
                $format = 'Y-m-d';
                break;
            default:
                $format = $type;
                break;
        }
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
     * @param {Object} $time	时间戳
     */
    function getUtcTime($time)
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
        $mydatetime = new \DateTime($dtStr);
        $expiration = $mydatetime->format(\DateTime::ISO8601);
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
     * 默认post
     *
     * @param $url http://www.df.net
     * @param $data ["a"=>123]
     * @param $header ["Content-Type: application/json"]
     * @param $type
     **/
    public function httpRequest($url, $data = null, $type = self::REQ_POST, $header = null)
    {
        //初始化浏览器
        $curl = curl_init();
        switch ($type) {
            case self::REQ_JSON:
                if (!empty($data)) {
                    $data = json_encode($data, JSON_UNESCAPED_UNICODE);
                    curl_setopt($curl, CURLOPT_HEADER, false);
                    curl_setopt(
                        $curl,
                        CURLOPT_HTTPHEADER,
                        array(
                            'Content-Type: application/json; charset=utf-8',
                            'Content-Length:' . strlen($data),
                            'Cache-Control: no-cache',
                            'Pragma: no-cache'
                        )
                    );
                }
                break;
            case self::REQ_GET:
                //判断data是否有数据
                if (!empty($data)) {
                    $url .= '?';
                    foreach ($data as $k => $v) {
                        $url .= \sprintf("%s=%s&", $k, $v);
                    }
                    $data = null;
                }
                break;
            case self::REQ_POST:
                break;
            default:
                # code...
                break;
        }

        //设置header头
        if (!empty($header)) {
            $header_list = [];
            foreach ($header as $k => $v) {
                $header_list[] = \sprintf("%s:%s", $k, $v);
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header_list);
        }

        //判断data是否有数据
        if (!empty($data)) {
            //设置POST请求方式
            curl_setopt($curl, CURLOPT_POST, true);
            //设置POST的数据包
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        // 支持https请求
        if (1 == strpos("$" . $url, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        // 当遇到location跳转时，直接抓取跳转的页面，防止出现301
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        //设置浏览器，把参数url传到浏览器的设置当中
        curl_setopt($curl, CURLOPT_URL, $url);
        // 50s延迟
        curl_setopt($curl, CURLOPT_TIMEOUT, 50);
        //禁止https协议验证ssl安全认证证书
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        //禁止https协议验证域名，0就是禁止验证域名且兼容php5.6
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        // 以字符串形式返回到浏览器当中
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // 让curl发起网络请求
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
        // 关闭 cURL 句柄
        curl_close($curl);
        return $ret;
    }

    /**
     * 获取页面html
     * @param {Object} $url 地址
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
     * 获取页面状态
     * 可判断远程文件是否存在(如果网站做过404处理，就检测不出来)
     *
     * @param {Object} $url 地址
     * @return {Object} false 页面不存在 true 页面存在
     */
    public function getHtmlStatus($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 在 cURL 请求中不包括响应体（即不包括实际的页面内容）
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        // 如果 HTTP 请求返回一个错误状态码（例如 404 Not Found），curl_exec 将返回 false
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        // 将 cURL 获取的内容作为字符串返回，而不是直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $status = curl_exec($ch);
        if ($status !== false) {
            return true;
        } else {
            return false;
        }
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
     * 在字符串中查找指定字符串，从1开始计算
     * 存在则返回大于0的数字，不存在则返回0
     *
     * eg:findstr('abc','c')
     */
    public function findStr($find, $str)
    {
        $pos = strpos($find, $str);
        //	echo $pos;
        if ((bool)$pos) {
            $rt = $pos + 1;
        } else {
            $rt = 0;
        }

        return $rt;
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
     */
    public function arr2url($data)
    {
        return http_build_query($data);
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
        return  $str;
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
        if ($strAuthUser == $account &&  $strAuthPass == $password) {
            return true;
        }
        //验证失败
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
    public function setHttpStatus($var = self::OK)
    {
        http_response_code($var);
        switch ($var) {
            case self::MOVED_PERMANENTLY:
                die("永久重定向");
            case self::UNAUTHORIZED:
                die("未经授权");
            case self::FORBIDDEN:
                die("禁止访问");
            case self::NOT_FOUND:
                die("页面没找到");
            case self::OK:
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
        return sprintf("%s-%s", self::getBrowserName(), self::getBrowserVer());
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
     * 拼装随机数，保留0位小数，生成一个字符串
     */
    public function byteFormat($input, $prec = 0)
    {
        $prefix_arr = array('D', 'F', 'E', 'R');
        $value = round($input, $prec);
        $i = 0;
        while ($value > 1024) {
            $value /= 1024;
            $i++;
        }
        $return_str = round($value, $prec) . $prefix_arr[$i];
        return $return_str;
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
        $obj = new \stdClass();
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
                $var[htmlspecialchars($key)] = self::ihtmlspecialchars($value);
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
        //	 echo exec('whoami');
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
     **/
    public function bubbleSort(array $arr, $item_name, $is_asc = false)
    {
        for ($i = 0; $i < count($arr); $i++) {
            $data = '';
            for ($j = $i + 1; $j < count($arr); $j++) {
                if ($arr[$i][$item_name] < $arr[$j][$item_name]) {
                    $data      = $arr[$i];
                    $arr[$i]   = $arr[$j];
                    $arr[$j] = $data;
                }
            }
        }
        $arr = $is_asc ? array_reverse($arr, false) : $arr;
        return $arr;
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
     * @param int  $type 类型。TODBC:转换为半角；TOSBC，转换为全角
     * @return string 返回转换后的字符串
     */
    public function convertStrType($str, $type)
    {
        // 全角
        $dbc = array(
            '０', '１', '２', '３', '４',
            '５', '６', '７', '８', '９',
            'Ａ', 'Ｂ', 'Ｃ', 'Ｄ', 'Ｅ',
            'Ｆ', 'Ｇ', 'Ｈ', 'Ｉ', 'Ｊ',
            'Ｋ', 'Ｌ', 'Ｍ', 'Ｎ', 'Ｏ',
            'Ｐ', 'Ｑ', 'Ｒ', 'Ｓ', 'Ｔ',
            'Ｕ', 'Ｖ', 'Ｗ', 'Ｘ', 'Ｙ',
            'Ｚ', 'ａ', 'ｂ', 'ｃ', 'ｄ',
            'ｅ', 'ｆ', 'ｇ', 'ｈ', 'ｉ',
            'ｊ', 'ｋ', 'ｌ', 'ｍ', 'ｎ',
            'ｏ', 'ｐ', 'ｑ', 'ｒ', 'ｓ',
            'ｔ', 'ｕ', 'ｖ', 'ｗ', 'ｘ',
            'ｙ', 'ｚ', '－', '　', '：',
            '．', '，', '／', '％', '＃',
            '！', '＠', '＆', '（', '）',
            '＜', '＞', '＂', '＇', '？',
            '［', '］', '｛', '｝', '＼',
            '｜', '＋', '＝', '＿', '＾',
            '￥', '￣', '｀'
        );
        //半角
        $sbc = array(
            '0', '1', '2', '3', '4',
            '5', '6', '7', '8', '9',
            'A', 'B', 'C', 'D', 'E',
            'F', 'G', 'H', 'I', 'J',
            'K', 'L', 'M', 'N', 'O',
            'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y',
            'Z', 'a', 'b', 'c', 'd',
            'e', 'f', 'g', 'h', 'i',
            'j', 'k', 'l', 'm', 'n',
            'o', 'p', 'q', 'r', 's',
            't', 'u', 'v', 'w', 'x',
            'y', 'z', '-', ' ', ':',
            '.', ',', '/', '%', ' #',
            '!', '@', '&', '(', ')',
            '<', '>', '"', '\'', '?',
            '[', ']', '{', '}', '\\',
            '|', '+', '=', '_', '^',
            '￥', '~', '`'
        );

        if ($type == 'TODBC') {
            //半角到全角
            return str_replace($sbc, $dbc, $str);
        } elseif ($type == 'TOSBC') {
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
        if (!isset($obj) || $obj === null || self::trimAll($obj) === '') {
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
        return self::str(shell_exec($var));
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
     * @param {Object} $string	字符串
     * @param {Object} $params	参数
     */
    public function str($string, $params = [])
    {
        $string = is_string($string) ? $string : var_export($string, true);

        foreach ($params as $key => $value) {
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
     * @param {Object} $str	原始字符串
     * @param {Object} $length	缩略文本长度
     */
    public function getThumbnailText($str, $length)
    {
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
     * @param string       $haystack
     * @param string|array $needles
     * @return bool
     */
    public function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查字符串是否以某些字符串结尾
     *
     * @param  string       $haystack
     * @param  string|array $needles
     * @return bool
     */
    public function endsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle === static::substr($haystack, -static::length($needle))) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查字符串是否以某些字符串开头
     *
     * @param  string       $haystack
     * @param  string|array $needles
     * @return bool
     */
    public function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取指定长度的随机字母数字组合的字符串
     *
     * @param  int $length
     * @return string
     */
    public function random($length = 16)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return static::substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }

    /**
     * 字符串转小写
     *
     * @param  string $value
     * @return string
     */
    public function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * 字符串转大写
     *
     * @param  string $value
     * @return string
     */
    public function upper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * 获取字符串的长度
     *
     * @param  string $value
     * @return int
     */
    public function length($value)
    {
        return mb_strlen($value);
    }

    /**
     * 截取字符串
     *
     * @param  string   $string
     * @param  int      $start
     * @param  int|null $length
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
     * @param  string $value
     * @param  string $delimiter
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

            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }

        return static::$snakeCache[$key][$delimiter] = $value;
    }

    /**
     * 下划线转驼峰(首字母小写)
     *
     * @param  string $value
     * @return string
     */
    public function camel($value)
    {
        if (isset(static::$camelCache[$value])) {
            return static::$camelCache[$value];
        }

        return static::$camelCache[$value] = lcfirst(static::studly($value));
    }

    /**
     * 下划线转驼峰(首字母大写)
     *
     * @param  string $value
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
     * @param  string $value
     * @return string
     */
    public function title($value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * 替换字符串
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
     * @param {Object} $str	字符串
     * @param {Object} $file2html	true 文件转html false html转文件
     */
    public function newlineConversion($str, $type = self::NL_CRLF2BR)
    {
        switch ($type) {
            case self::NL_CRLF2BR:
                $ret = str_replace(PHP_EOL, "<br />", $str);
                break;
            case self::NL_BR2CRLF:
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
    public function getException(\Exception $exception)
    {
        $trace_list = [];
        $trace_list[] = $this->str("%s %s", [$exception->getFile(), $exception->getLine()]);
        $trace_list[] = "";
        foreach ($exception->getTrace() as $key => $value) {
            if (empty($value['file']))
                continue;
            $trace_list[] = $this->str("%s %s", [$value['file'], $value['line']]);
        }

        $err_msg = $this->str(<<<STR
		////////////////////////////////////////////////// 出错 START //////////////////////////////////////////////////
		{0}
		
		{1}
		//////////////////////////////////////////////////  出错 END  //////////////////////////////////////////////////
		
		STR, [$exception->getMessage(), implode(PHP_EOL, $trace_list)]);

        return $err_msg;
    }
	
	
	/**
	 * 判断当前页面是否本地域名
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
	 * 	Common::buildParentTree($portalCategoryModel->field('id,parent_id,name')->select()->toArray(),$categories[0]['id'])
	 * @param {Object} $categories
	 * @param {Object} $parentId
	 */
	public function buildParentTree($categories, $parentId) {
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
	public function traverseParentTree($array,$list=[]) {  
	    if (isset($array['id'])&&isset($array['name'])) {  
			$list[$array['id']]=$array['name'];
	    } 
	    if (isset($array['parent']) && is_array($array['parent'])) {  
	        $list=$this->traverseParentTree($array['parent'],$list);  
	    }
		// 通过key进行正序排列
		ksort($list);
		return $list;
	}  
	
	/**
	 * 通过菜单子级id获取所有的父级id，返回从小到大的目录数组
	 * eg:
	 * 	Common::buildParentTreeMenu($portalCategoryModel->field('id,parent_id,name')->select()->toArray(),$categories[0]['id'])
	 * @param {Object} $categories
	 * @param {Object} $parentId
	 * @return {Array} 从小到大的目录结构 eg:[1=>"菜单一",5=>"菜单一(1)",7=>"菜单一(1)(1)"]
	 */
	public function buildParentTreeMenu($categories, $parentId) {
	    $categories_tree=$this->buildParentTree($categories,$parentId);
	    $list=$this->traverseParentTree($categories_tree);	    
	    return $list;  
	}
	
	
	
	/**
	 * 通过菜单父级id获取所有的子级id
	 * eg:
	 * 	Common::buildChildTree($portalCategoryModel->field('id,parent_id,name')->select()->toArray(),$categories[0]['id'])
	 * @param {Object} $categories
	 * @param {Object} $childId
	 */
	public function buildChildTree($categories, $childId) {
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
}
