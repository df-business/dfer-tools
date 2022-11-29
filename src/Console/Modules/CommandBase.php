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
 
    protected function execute(Input $in, Output $out)
    {
        global $input,$output,$debug,$common_base;
        $common_base=new CommonBase();
        $debug=false;
        $input=$in;
        $output=$out;
        $common_base->debug_print('程序开始...');
        $this->init();
        $common_base->debug_print('程序结束');
    }
}
