<?php

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

namespace Dfer\Tools\TpConsole;

use Exception;
use think\console\{Command as BaseCommand, Input, Output};
use think\console\input\{Argument, Option};
use Dfer\Tools\Statics\Common;
use Dfer\Tools\Constants;

class Command extends BaseCommand
{
    // 调试模式
    protected $debug;
    // >=tp6
    protected $is_new_tp;
    // tp版本详情
    protected $tp_ver;
    // 项目根目录
    protected $root;
    // 项目commond目录
    protected $root_commond;
    // 当前脚本运行目录
    protected $cur_dir;
    // tp控制台输入、输出对象
    protected $input, $output;
    // 当前命令名称。比如：dfer:console_create
    protected $command;

    /**
     * 默认选项
     */
    protected function configure()
    {
        $this->addOption('about', 'a', Option::VALUE_NONE, '简介');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $this->checkTp();
            $class_name = get_class($this);
            $this->input = $input;
            $this->output = $output;
            $this->cur_dir = realpath(__DIR__);
            $this->debug = Common::objToBool($input->getOption('debug'));
            $debug = $this->debug ? '开' : '关';
            $this->command = $input->getArgument('command');
            if ($this->input->hasOption('about')) {
                $about = $this->input->getOption('about');
                if ($about) {
                    $this->tpPrint(<<<STR

                    | AUTHOR: dfer
                    | EMAIL: df_business@qq.com
                    | QQ: 3504725309
                    | ThinkPHP: v{$this->tp_ver}
                    |
                    | 项目: {$this->root}
                    | 指令: {$this->root_commond}
                    | 运行: {$this->cur_dir}
                    | 调试: {$debug}

                    STR);
                    exit();
                }
            }

            $this->tpPrint(Common::str('////////////////////////////////////////////////// {0} 开始 //////////////////////////////////////////////////', [$class_name]), Constants::COLOR_ECHO);
            echo PHP_EOL;
            $this->init();
            echo PHP_EOL;
            $this->tpPrint(Common::str('////////////////////////////////////////////////// {0} 结束 //////////////////////////////////////////////////', [$class_name]), Constants::COLOR_ECHO);
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
            // <tp6
            $this->is_new_tp = false;
            $this->tp_ver = THINK_VERSION;
            $this->root = ROOT_PATH;
            $this->root_commond = "{$this->root}/application/api/command/";
            \think\Lang::load(APP_PATH . 'lang/zh-cn.php');
        } else {
            // >=tp6
            $this->is_new_tp = true;
            $this->tp_ver = app()->version();
            $this->root = app()->getRootPath();
            $this->root_commond = "{$this->root}app/command";
            \think\facade\Lang::load( app()->getAppPath() . 'lang/zh-cn.php');
        }
    }

    /**
     * 控制台打印、日志记录
     */
    public function tpPrint($str, $type = Constants::CONSOLE_WRITE, $textColor = 37, $bgColor = 45)
    {
        global $argv;

        // 后台运行时调用"CONSOLE_WRITE"会导致后台服务堵塞
        if ($type == Constants::CONSOLE_WRITE && isset($argv[2]) && $argv[2] == '-d') {
            $type = Constants::STDOUT_WRITE;
        }

        switch ($type) {
            case Constants::CONSOLE_WRITE:
                // tp控制台输出
                $this->output->newLine();
                $this->output->writeln(sprintf(">>>>>>>>>>>>%s", $str), Output::OUTPUT_NORMAL);
                break;
            case Constants::LOG_WRITE:
                // 写日志
                Common::debug($str);
                break;
            case Constants::COLOR_ECHO:
                // 带颜色输出
                echo PHP_EOL;
                Common::colorEcho(sprintf("%s", $str), $textColor, $bgColor);
                echo PHP_EOL;
                break;
            case Constants::STDOUT_WRITE:
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
    public function debugPrint($str, $textColor = null, $bgColor = null)
    {
        if ($this->debug) {
            $str = substr(json_encode($str, JSON_UNESCAPED_UNICODE), 1, -1);
            $this->tpPrint($str, Constants::COLOR_ECHO, $textColor, $bgColor);
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

    /**
     * 生成json字符串
     * @param {Object} $data
     * @param {Object} $msg 信息
     * @param {Object} $status 状态
     */
    public function showJson($data, $msg, $status = true)
    {
        $msg = $msg ?: (boolval($status) ? '操作成功' : '操作失败');

        $return = array(
            'status' => $status,
            'msg' => $msg
        );
        if ($data) {
            $return['data'] = $data;
        }

        return json_encode($return, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 成功
     * @param {Object} $data
     * @param {Object} $msg 信息
     */
    public function success($data, $msg = "")
    {
        return $this->showJson($data, $msg, true);
    }

    /**
     * 失败
     * @param {Object} $data
     * @param {Object} $msg 信息
     */
    public function fail($data, $msg = "")
    {
        return $this->showJson($data, $msg, false);
    }
}
