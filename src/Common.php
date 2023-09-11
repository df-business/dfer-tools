<?php

namespace Dfer\Tools;

/**
 * +----------------------------------------------------------------------
 * | 常用的方法
 * +----------------------------------------------------------------------
 *       .::::.
 *     .::::::::.            | AUTHOR: dfer
 *     :::::::::::           | EMAIL: df_business@qq.com
 *  ..:::::::::::'           | QQ: 3504725309
 *             '::::::::::::'
 * .::::::::::
 *           '::::::::::::::..
 * ..::::::::::::.
 *              ``::::::::::::::::
 *::::``:::::::::'        .:::.
 *              ::::'   ':::::'       .::::::::.
 *            .::::'      ::::     .:::::::'::::.
 *           .:::'       :::::  .:::::::::' ':::::.
 *          .::'        :::::.:::::::::'      ':::::.
 *         .::'         ::::::::::::::'         ``::::.
 *     ...:::           ::::::::::::'              ``::.
 *   ```` ':.          ':::::::::'   ::::..
 *       '.:::::'     ':'````..
 * +----------------------------------------------------------------------
 *
 */
class Common
{
    
    
    /**
     * 简介
     *
     * @param Type $var Description
     * @return mixed
     **/
    public static function about()
    {
        $host='http://www.dfer.site';
        header("Location:".$host);
        return $host;
    }
    
    /**
     * 打印
     **/
    public static function print($str=null)
    {
        echo json_encode($str, JSON_UNESCAPED_UNICODE).PHP_EOL;
    }
   
    /**
     * 把mysql导出的json文本拼接成数组字符串
     **/
    public static function mysqlJsonToArray($str=null)
    {
        $arr=json_decode($str);
        $item=$arr->RECORDS;
        // var_dump($item);
        
        $name=[];
        foreach ($item as $key => $value) {
            $name[]=$value->name;
        }
        $result=sprintf('["%s"]', join('","', $name));
        return $result;
    }
 
 
 
    /**
     * 输出json，然后终止当前请求
     */
    public function showJson($status = 1, $return = array(), $msg='')
    {
        $ret = array(
         'status' => $status,
         'msg'=>$msg
     );
        if ($return) {
            $ret['result'] = $return;
        }
     
        showJsonBase($ret);
    }
 
    public function showJsonBase($return = array())
    {
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
        if ($_SERVER["HTTPS"]=="on") {
            $xredir="http://".$_SERVER["SERVER_NAME"].
 $_SERVER["REQUEST_URI"];
            header("Location: ".$xredir);
        } else {
            $xredir="https://".$_SERVER["SERVER_NAME"].
 $_SERVER["REQUEST_URI"];
            header("Location: ".$xredir);
        }
    }
 
 
 
    /**
     * 将时间戳转化为正常的时间格式
     *
     * eg:
     * getTime($output["time"],"Y/m/d H:i:s")
     *
     */
    public function getTime($time='', $type=0)
    {
        if ($type==0) {
            $str='Y-m-d H:i:s';
        } elseif ($type==1) {
            $str='Y-m-d';
        } else {
            $str='Y.m.d';
        }
        if (!empty($time)) {
            return date($str, $time);
        } else {
            return date("Y-m-d H:i:s");
        }
    }
 
    /**
     * 将时间字符串转化为时间戳，格式化之后转化为正常的时间格式
     *
     * eg:
     * getTimeFromStr($output["time"],"Y/m/d H:i:s")
     *
     *
     */
    public function getTimeFromStr($time, $type=0)
    {
        if (is_numeric($type)) {
            if ($type==0) {
                $str='Y-m-d H:i:s';
            } elseif ($type==1) {
                $str='Y-m-d';
            }
        } else {
            $str=$type;
        }
        //date_default_timezone_set('Asia/Shanghai'); //设置为东八区上海时间
        return date($str, strtotime($time));
    }
 
 
    //-------------------------------session
  
    /**
     * 服务器缓存
     *
     *  默认情况下，PHP.ini 中设置的 SESSION 保存方式是 files（session.save_handler = files），即使用读写文件的方式保存 SESSION 数据，而 SESSION 文件保存的目录由 session.save_path 指定
     *
     *  当写入 SESSION 数据的时候，php 会获取到客户端的 SESSION_ID，然后根据这个 SESSION ID 到指定的 SESSION 文件保存目录中找到相应的 SESSION 文件，不存在则创建之
     *
     *
     * 不同浏览器的session不一样
     *
     * 浏览器主窗与无痕窗的ses不一样
     * 经测试，safari多个无痕窗的ses是独立的，但chrome多个无痕窗的ses是公用的
     *
     * 清空浏览器缓存无法影响session
     *
     * Session默认的生命周期通常是20分钟
     */
    public function getSession($name)
    {
        if (!empty($_SESSION[$name])) {
            $rt=$_SESSION[$name];
        } else {
            $rt="";
        }
        return $rt;
    }
    public function setSession($name, $val, $rt="")
    {
        $_SESSION[$name]=$val;
        if ($rt!="") {
            header(sprintf("location:%s", SplitUrl($rt)));
        }
    }
 
    /**
     * 删除ses并跳转页面
     */
    public function delSession($name='', $rt="")
    {
        if (empty($name)) {
            session_destroy();
        } else {
            unset($_SESSION[$name]);
        }
        if (empty($rt)) {
            header('location: '.URL);
        } else {
            header(sprintf("location:%s", SplitUrl($rt)));
        }
    }
 
        
    //-------------------------------other
 
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
            $unicodeStr .= "&#".base_convert(bin2hex(iconv('UTF-8', "UCS-4", $m)), 16, 10);
        }
        return $unicodeStr;
    }
   
    /**
     * unicode解密
     * @param {Object} $unicode_str
     */
    public function unicodeDecode($unicode_str)
    {
        $json = '["'.$unicode_str.'"]';
        $arr = json_decode($json, true);
        if (empty($arr)) {
            return '';
        }
        return is_array($arr)?$arr[0]:$arr;
    }
 
 
    const REQ_JSON=0,REQ_GET=1,REQ_POST=2;
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
    
            //以字符串形式返回到浏览器当中
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            //让curl发起请求
            $output = curl_exec($curl);
            //关闭curl浏览器
            curl_close($curl);
            $rt = json_decode($output, true);
            if (empty($rt)) {
                $rt = $output;
            }
            return $rt;
        }
    }
 
    /**
     * 将get的参数字符串组装成数组
     * @param {Object} $str
     */
    public function getPara($str)
    {
        $str=explode("&", $str);
        foreach ($str as $i) {
            $i=explode("=", $i);
            $rt[$i[0]]=$i[1];
        }
        return $rt;
    }
 
    /**
     * 判断远程文件是否存在
     * 如果代码做过404处理就检测不出来
     */
    public function httpExist($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, 1); // 不下载
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
        if (curl_exec($ch)!==false) {
            return true;
        } else {
            return false;
        }
    }
    
    
    
    /**
     * 在字符串中查找指定字符串，从1开始计算
     * 存在则返回大于0的数字，不存在则返回0
     *
     * eg:findstr('abc','c')
     */
    public function findstr($find, $str)
    {
        $pos=strpos($find, $str);
        //	echo $pos;
        if ((bool)$pos) {
            $rt=$pos+1;
        } else {
            $rt=0;
        }
        
        return $rt;
    }
        
        
        
    /**
     * 去掉空格和回车
     * @param {Object} $str
     */
    public function delSpace($str)
    {
        $str=trim($str);
        $str=ltrim($str)."\n";
        $str=ltrim($str, " ");
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
    public function html($str, $encode=true)
    {
        if ($encode) {
            return htmlspecialchars($str);
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
        $chinese=implode("", $chinese[0]);
        
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
     *
     * 跳转到指定url，并携带参数
     * 可以不带参数
     *
     * 主要用来显示form错误信息
     * eg：
     * ToUrl('http://www.qq.com');
     * ToUrl("wx/home/wxshare",array('WxId'=>$_df[ 'wxId']));
     *
     */
    public function toUrl($url, $para=null)
    {
        if (!empty($para)) {
            $url=SplitUrl($url);
            $para=http_build_query($para);
            $url="location:{$url}&{$para}";
        } else {
            $url=SplitUrl($url);
            $url="location:{$url}";
        }
        
        header($url);
        die();
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
            $v = pack("H".strlen(base_convert($v, 2, 16)), base_convert($v, 2, 16));
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
        $hex="";
        for ($i=0;$i<strlen($str);$i++) {
            $hex.=dechex(ord($str[$i]));
        }
        $hex=strtoupper($hex);
        return $hex;
    }
    
    /**
     * 十六进制转字符串函数
     * @pream string $hex='616263';
     *
     */
    public function hexToStr($hex)
    {
        $str="";
        for ($i=0;$i<strlen($hex)-1;$i+=2) {
            $str.=chr(hexdec($hex[$i].$hex[$i+1]));
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
        foreach ($arr as $key=>$v) {
            //兼容wq
            $key=str_replace(':', '', $key);
            $str = preg_replace("/:{$key}/", is_string($v)?"'{$v}'":$v, $str);
        }
        return $str;
    }
    
    
    
    /**
     * php调用网页头的验证功能
     */
    public function webAuthenticate($ac, $pw)
    {
        $strAuthUser= $_SERVER['PHP_AUTH_USER'];
        $strAuthPass= $_SERVER['PHP_AUTH_PW'];
    
        //验证成功
        if ($strAuthUser == $ac &&  $strAuthPass == $pw) {
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
            $clientkeywords = array('nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile','MicroMessenger');
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
 
 
    //获取浏览器内核信息
    public function getBrowser()
    {
        return sprintf("%s-%s", $this -> getBrowser_name(), $this -> getBrowser_ver());
    }
 
    //ie兼容性差，对ie内核进行警告
    public function ieNotice()
    {
        //	echo $this->getBrowser_name();
        if ($this -> getBrowser_name() == 'ie') {
            show_message("不支持IE内核", "请检查浏览器");
        }
    }
 
    public function getBrowserName()
    {
        $agent = $_SERVER["HTTP_USER_AGENT"];
        if (strpos($agent, 'MSIE') !== false || strpos($agent, 'rv:11.0')) {//ie11判断
            return "ie";
        } elseif (strpos($agent, 'Firefox') !== false) {//火狐
            return "firefox";
        } elseif (strpos($agent, 'Chrome') !== false) {//谷歌
            return "chrome";
        } elseif (strpos($agent, 'Opera') !== false) {//opera
            return 'opera';
        } elseif ((strpos($agent, 'Chrome') == false) && strpos($agent, 'Safari') !== false) {
            return 'safari';
        }
    }
 
    public function getBrowserVer()
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {//当浏览器没有发送访问者的信息的时候
            return 'unknow';
        }
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/MSIE\s(\d+)\..*/i', $agent, $regs)) {//IE浏览器版本号
            return $regs[1];
        } elseif (preg_match('/FireFox\/(\d+)\..*/i', $agent, $regs)) {//火狐浏览器版本号
            return $regs[1];
        } elseif (preg_match('/Opera[\s|\/](\d+)\..*/i', $agent, $regs)) {//opera浏览器版本号
            return $regs[1];
        } elseif (preg_match('/Chrome\/(\d+)\..*/i', $agent, $regs)) {//谷歌浏览器版本号
            return $regs[1];
        } elseif ((strpos($agent, 'Chrome') == false) && preg_match('/Safari\/(\d+)\..*$/i', $agent, $regs)) {
            return $regs[1];
        } else {
            return 'unknow';
        }
    }
  
 
    //拼装随机数，保留0位小数，生成一个字符串
    public function byteFormat($input, $prec = 0)
    {
        $prefix_arr = array('D', 'F', 'E', 'R', 'R');
        $value = round($input, $prec);
        $i = 0;
        while ($value > 1024) {
            $value /= 1024;
            $i++;
        }
        $return_str = round($value, $prec) . $prefix_arr[$i];
        return $return_str;
    }
 
    //数组中所有元素都是数组则返回true
    public function isArray2($array)
    {
        if (is_array($array)) {
            foreach ($array as $k => $v) {
                return is_array($v);
            }
        }
        return false;
    }
 
    //获取最后一天的日期
    public function getLastDay($year, $month)
    {
        return date('t', strtotime("{$year}-{$month} -1"));
    }
 
    /**
     *
     * 判断是否是时间戳
     */
    public function isTimestamp($timestamp)
    {
        if (strtotime(date('m-d-Y H:i:s', $timestamp)) === $timestamp) {
            return $timestamp;
        } else {
            return false;
        }
    }
 
    //base64加密
    public function b64Encode($obj)
    {
        if (is_array($obj)) {
            return urlencode(base64_encode(json_encode($obj)));
        }
 
        return urlencode(base64_encode($obj));
    }
 
    //base64解密
    public function b64Decode($str, $is_array = true)
    {
        $str = base64_decode(urldecode($str));
 
        if ($is_array) {
            return json_decode($str, true);
        }
 
        return $str;
    }
 
    
    //将数组中的元素进行html原样输出
    public function iHtmlspecialchars($var)
    {
        if (is_array($var)) {
            foreach ($var as $key => $value) {
                $var[htmlspecialchars($key)] = $this -> ihtmlspecialchars($value);
            }
        } else {
            $var = str_replace('&amp;', '&', htmlspecialchars($var, ENT_QUOTES));
        }
        return $var;
    }
 
    //获取当前页面的完整网址
    public function getCurUrl($getPort = 0)
    {
        if (!$getPort) {
            return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            return 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        }
        //包含端口号的完整url
    }
 
    //执行控制台命令
    public function runShell($shell)
    {
        //	 echo exec('whoami');
        echo exec($shell);
    }
 
    //获取"/"后的字符串
    public function getLast($file_name)
    {
        $a = explode('/', $file_name);
        return array_pop($a);
        //获取数组最后一条数据
         //用／号对字符串进行分组
    }
 
   
    //分割字符串
    //Split("1|2|3","|");
    public function split($str, $char)
    {
        $rt = explode($char, $str);
        return $rt;
    }
   
 
    //生成一个指定大小的数组
    public function defineArr($a)
    {
        $array = array();
        for ($i = 0; $i < $a; $i++) {
            $array[$i] = $i;
        }
        return $array;
    }
    
    /**
     * 独立日志
     *
     * 'apart_level'=>['error','sql','debug','dfer']
     **/
    public function log($data, $identification='dfer')
    {
        if (class_exists("\\think\\facade\\Log")) {
            \think\facade\Log::write($data, $identification);
        } else {
            \think\Log::write($data, $identification);
        }
    }
    
    /**
     * 对象转bool
     **/
    public function objToBool($obj)
    {
        if (is_numeric($obj)) {
            return intval($obj)>0;
        } elseif (is_string($obj)) {
            return strtolower($obj)=='true';
        } else {
            return boolval($obj);
        }
    }
    
    /**
     * @Description: 将时间转换为几秒前、几分钟前、几小时前、几天前
     * @param $the_time 需要转换的时间。时间戳或者时间字符串
     * @return string
     */
    public function time_tran($the_time)
    {
        $now_time = date("Y-m-d H:i:s", time());
        $now_time =strtotime($now_time);
        $show_time =is_numeric($the_time)?$the_time:strtotime($the_time);
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
        $begin_time =is_numeric($begin_time)?$begin_time:strtotime($begin_time);
        $subTime = time()- $begin_time;
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
    public function bubbleSort(array $arr, $item_name, $is_asc=false)
    {
        for ($i=0 ; $i <count($arr) ; $i++) {
            $data = '';
            for ($j=$i+1 ; $j < count($arr); $j++) {
                if ($arr[$i][$item_name] < $arr[$j][$item_name]) {
                    $data      = $arr[$i];
                    $arr[$i]   = $arr[$j];
                    $arr[$j] = $data;
                }
            }
        }
        $arr=$is_asc?array_reverse($arr, false):$arr;
        return $arr;
    }
    
    
    /**
     * 根据奖品概率，进行随机抽奖，返回中奖的编号
     * 每行数据的值必须大于等于0，相对数值越大则中奖概率越高，反之则概率越小
     * 所有数值的总和不受限制，根据每条数据相对于总和的概率依次进行筛选
     * 原理：将所有值进行累加，用单个值占总值的比例作为该项的概率，该值占总值的比例越大则概率越高，反之越小
     * @param {Object} $proArr
     */
    public function get_prize($proArr)
    {
        $result = '';
        //概率数组的总概率精度。数组值的总和
        $proSum = array_sum($proArr);
        if ($proSum==0) {
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
}
