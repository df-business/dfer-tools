<?php
declare(strict_types = 1);
namespace Dfer\Tools\Console\Modules;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * +----------------------------------------------------------------------
 * | console基础类，继承自Command
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
class CommandBase extends Command
{
    public static $common_base;
    public static $debug;
    public static $db;
    public static $tp_new;
    
    protected function execute(Input $in, Output $out)
    {
        global $input,$output,$common_base,$debug,$db,$tp_new;
        $input=$in;
        $output=$out;
        self::$common_base=$common_base=new CommonBase();
        self::$debug=$debug = $common_base->objToBool($input->getOption('debug'));
        self::$db=$db;
        self::$tp_new=$tp_new;
        $common_base->debug_print('程序开始...');
        $this->init();
        $common_base->debug_print('程序结束');
    }
}
