<?php
declare(strict_types = 1);
namespace Dfer\Tools\Ws\Modules;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

use think\facade\Db;

class Common extends Command
{
    // 控制台输出
    const CONSOLE_WRITE=0;
    // tp日志
    const LOG_WRITE=1;
    // worker日志
    const STDOUT_WRITE=2;
        
    protected function execute(Input $in, Output $out)
    {
        global $db,$input,$output;
        $input=$in;
        $output=$out;
        // $db =new Db;
        $this->print('程序开始...');
        $this->init();
        $this->print('程序结束');
    }
  
    /**
     * 控制台打印、日志记录
     **/
    public function print($str, $type=self::CONSOLE_WRITE)
    {
        $class_src=get_class($this);
        switch ($type) {
                    case self::CONSOLE_WRITE:
                    // 后台运行时调用此方法会导致后台服务堵塞
                        global $output;
                        $output->newLine();
                        $output->writeln(sprintf("[%s]%s", $class_src, $str), Output::OUTPUT_NORMAL);
                        break;
                    case self::LOG_WRITE:
                        \think\facade\Log::write($str, $this->last_slash_str($class_src));
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
        global $argv;
        $str=substr(json_encode($str, JSON_UNESCAPED_UNICODE), 1, -1);
        if (isset($argv[2])&&$argv[2]=='-d') {
            $this->print($str, self::STDOUT_WRITE);
        } else {
            $this->print($str, self::CONSOLE_WRITE);
        }
        $this->print($str, self::LOG_WRITE);
    }
        
    
    
    /**
     * json字符串
     **/
    public function json($status=0, $data=[], $msg='')
    {
        $json = array('status' => $status, 'msg' =>$msg?$msg:($status!=0?'出错':'成功'), 'data' =>$data);
        return json_encode($json);
    }
    
    // 传递字符串
    public function msg($msg='')
    {
        $ret=$this->json(0, [], $msg);
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
}
