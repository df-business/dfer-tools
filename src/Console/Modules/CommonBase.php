<?php
declare(strict_types = 1);
namespace Dfer\Tools\Console\Modules;

use think\console\Output;
use Dfer\Tools\Common;
use think\Cache;

/**
 * +----------------------------------------------------------------------
 * | php基础类，继承自通用类
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

class CommonBase extends Common
{
    // 控制台输出
    const CONSOLE_WRITE=0;
    // tp日志
    const LOG_WRITE=1;
    // worker日志
    const STDOUT_WRITE=2;

    /**
     * 实例化的时候被调用
     */
    public function __construct()
    {
        global $db,$tp_ver,$tp_new;
        // tp5与tp6调用方式不同
        if (class_exists("\\think\\facade\\Db")) {
            // tp6
            $tp_new=true;
            $tp_ver=app()->version();
            $db = new \think\facade\Db();
        } else {
            // tp5以下
            $tp_new=false;
            $tp_ver=THINK_VERSION;
            $db = new \think\Db();
        }
    }

    /**
     * 控制台打印、日志记录
     **/
    public function tp_print($str, $type=self::CONSOLE_WRITE)
    {
        global $argv,$tp_new,$class_src;
        // 后台运行时调用"CONSOLE_WRITE"会导致后台服务堵塞
        if ($type==self::CONSOLE_WRITE&&isset($argv[2])&&$argv[2]=='-d') {
            $type=self::STDOUT_WRITE;
        }
        $class_src=get_class($this);
        switch ($type) {
                    case self::CONSOLE_WRITE:
                        global $output;
                        $output->newLine();
                        $output->writeln(sprintf("[%s]%s", $class_src, $str), Output::OUTPUT_NORMAL);
                        break;
                    case self::LOG_WRITE:
                        $this->log($str, $this->last_slash_str($class_src));
                        break;
                    case self::STDOUT_WRITE:
                        echo sprintf("[%s]%s\n", $class_src, $str);
                        break;
                    default:
                        # code...
                        break;
                }
    }
        
    /**
     * 最后一个斜杠后的字符串
     **/
    public static function last_slash_str($str)
    {
        $arr=\explode("\\", $str);
        return $arr[count($arr)-1];
    }
        
    /**
     * 调试打印
     **/
    public function debug_print($str)
    {
        global $debug;
        if ($debug) {
            $str=substr(json_encode($str, JSON_UNESCAPED_UNICODE), 1, -1);
            $this->tp_print($str);
            $this->tp_print($str, self::LOG_WRITE);
        }
    }
        
    
    
    /**
     * json字符串
     **/
    public function json($type, $status=0, $data=[], $msg='')
    {
        $json = array('type' => $type,'status' => $status, 'msg' =>$msg?$msg:($status!=0?'出错':'成功'), 'data' =>$data);
        return json_encode($json);
    }
    
    /**
     * 传递字符串
     */
    public function msg($str='')
    {
        $ret=$this->json('msg', 0, [], $str);
        return $ret;
    }
    
    /**
     * 成功
     */
    public function success($type='', $data=[], $msg="")
    {
        $ret=$this->json($type, 0, $data, $msg);
        return $ret;
    }
    
    /**
     * 失败
     */
    public function fail($type='', $data=[], $msg="")
    {
        $ret=$this->json($type, -1, $data, $msg);
        return $ret;
    }
    
    /**
     * 时间戳转时间字符串
     **/
    public function timeToStr($timestamp)
    {
        $time =date("Y/m/d H:i:s", $timestamp);
        return $time;
    }
    
    /**
     * 设置缓存
     * @param {Object} $key
     * @param {Object} $val
     */
    protected function set_cache($key, $val)
    {
        return Cache::set($key, $val);
    }
    
    protected function get_cache($key)
    {
        return Cache::get($key);
    }
    
    /**
     * 创建文件
     */
    public function fileCreate($from, $to, $replace_list=[])
    {
        $path = dirname($to);
        $this->mkdirs($path);
        $from=file_get_contents($from);
        //替换
        foreach ($replace_list as $key => $value) {
            $from= str_replace($key, $value, $from);
        }
        // \var_dump($from);
        
        file_put_contents($to, $from);
    }
    
    /**
     * 更新配置文件
     */
    public function configUpdate($from_src, $replace_list=[])
    {
        global $tp_new;
        $this->mkdirs(dirname($from_src));
        $from=file_get_contents($from_src);
        
        
        
        //替换
        if ($tp_new) {
            foreach ($replace_list as $key => $value) {
                preg_match("/]([\s\S]*?)]/", $from, $str);
                if (count($str)>0) {
                    $from = preg_replace('/]([\s\S]*?)]/', $value, $from);
                }
            }
        } else {
            foreach ($replace_list as $key => $value) {
                preg_match("/return \[([\s\S]*?)]/", $from, $str);
                // \var_dump($str[0]);
                if (count($str)>0) {
                    // \var_dump($from);
                    $new_value = preg_replace('/]/', $value, $str[0]);
                    $from = preg_replace('/return \[([\s\S]*?)]/', $new_value, $from);
                    // \var_dump($from);
                }
            }
        }
        // \var_dump(stripos($from, $value), $str,$from);
        file_put_contents($from_src, $from);
    }
    /*
     * 创建目录
     *
     * 如果目录不存在就根据路径创建无限级目录
     */
    public function mkdirs($path)
    {
        //检查指定的文件是否是目录
        if (!is_dir($path)) {
            $this->mkdirs(dirname($path));//循环创建上级目录
            mkdir($path);
        }
        return is_dir($path);
    }
}
