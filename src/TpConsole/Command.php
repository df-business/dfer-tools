<?php
namespace Dfer\Tools\TpConsole;

use think\console\{Command as BaseCommand, Input, Output};
use think\console\input\{Argument, Option};
use Dfer\Tools\Statics\Common;
use Exception;

/**
 * +----------------------------------------------------------------------
 * | console基础类，继承自Command
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
class Command extends BaseCommand
{
    protected $debug;
    protected $is_new_tp, $tp_ver, $db;
    protected $input, $output;


    protected function execute(Input $input, Output $output)
    {
        try {
            $this->checkTp();
            $class_name = get_class($this);
            $this->input = $input;
            $this->output = $output;
            $this->debug = Common::objToBool($input->getOption('debug'));
            if ($this->input->hasOption('about')) {
                $about = $this->input->getOption('about');
                if ($about) {
                    $this->tpPrint(
                        <<<STR

| AUTHOR: dfer
| EMAIL: df_business@qq.com
| QQ: 3504725309
| ThinkPHP: v{$this->tp_ver}

STR
                    );
                    exit();
                }
            }

            Common::colorEcho(Common::str('////////////////////////////////////////////////// {0} 开始 //////////////////////////////////////////////////', [$class_name]));
            echo PHP_EOL;
            $this->init();
            echo PHP_EOL;
            Common::colorEcho(Common::str('////////////////////////////////////////////////// {0} 结束 //////////////////////////////////////////////////', [$class_name]));
            echo PHP_EOL;
        } catch (Exception $exception) {
            $err_msg = Common::getException($exception);
            echo $err_msg;
        }
    }

    /**
     * 检查tp版本
     */
    public function checkTp()
    {
        if (defined('THINK_VERSION')) {
            // 老版本
            $this->is_new_tp = false;
            $this->tp_ver = THINK_VERSION;
            \think\Lang::load(APP_PATH . 'lang/zh-cn.php');
        } else {
            // tp6以上
            $this->is_new_tp = true;
            $this->tp_ver = app()->version();
            \think\facade\Lang::load(APP_PATH . 'lang/zh-cn.php');
        }
    }

    /**
     * 控制台打印、日志记录
     */
    public function tpPrint($str, $type = Consts::CONSOLE_WRITE)
    {
        global $argv;

        // 后台运行时调用"CONSOLE_WRITE"会导致后台服务堵塞
        if ($type == Consts::CONSOLE_WRITE && isset($argv[2]) && $argv[2] == '-d') {
            $type = Consts::STDOUT_WRITE;
        }

        switch ($type) {
            case Consts::CONSOLE_WRITE:
                // tp控制台输出
                $this->output->newLine();
                $this->output->writeln(sprintf(">>>>>>>>>>>>%s", $str), Output::OUTPUT_NORMAL);
                break;
            case Consts::LOG_WRITE:
                // 写日志
                Common::debug($str);
                break;
            case Consts::STDOUT_WRITE:
            default:
                // 普通输出
                echo PHP_EOL;
                echo $str;
                echo PHP_EOL;
                break;
        }
    }

    /**
     * 调试打印
     **/
    public function debugPrint($str)
    {
        if ($this->debug) {
            $str = substr(json_encode($str, JSON_UNESCAPED_UNICODE), 1, -1);
            $this->tpPrint($str);
            $this->tpPrint($str, Consts::LOG_WRITE);
        }
    }

    /**
     * 创建文件
     */
    public function fileCreate($from, $to, $replace_list = [])
    {
        $from = Common::formatDirectorySeparator($from);
        $to = Common::formatDirectorySeparator($to);
        $path = dirname($to);
        Common::mkDirs($path);
        $from = file_get_contents($from);
        //替换
        foreach ($replace_list as $key => $value) {
            $from = str_replace($key, $value, $from);
        }
        // \var_dump($from);

        file_put_contents($to, $from);
    }

    /**
     * 更新配置文件
     */
    public function configUpdate($from_src, $replace_list = [])
    {
        Common::mkDirs(dirname($from_src));
        $from = file_get_contents($from_src);

        if ($this->is_new_tp) {
            foreach ($replace_list as $key => $value) {
                preg_match("/]([\s\S]*?)]/", $from, $str);
                if (count($str) > 0) {
                    if (strpos($from, $value) !== false) {
                        return;
                    }
                    $from = preg_replace('/]([\s\S]*?)]/', $value, $from);
                }
            }
        } else {
            foreach ($replace_list as $key => $value) {
                preg_match("/return \[([\s\S]*?)]/", $from, $str);
                // \var_dump($str[0]);
                if (count($str) > 0) {
                    if (strpos($from, $value) !== false) {
                        return;
                    }
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


}
