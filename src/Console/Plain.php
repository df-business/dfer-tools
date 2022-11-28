<?php

namespace Dfer\Tools\Console;

use think\console\input\Argument;
use think\console\input\Option;

# use Dfer\Tools\Console\Modules\Common;

/**
 * +----------------------------------------------------------------------
 * | 简单控制台
 * | eg:
 * | php think plain
 * | php think plain -t all
 * | php /www/wwwroot/xxx.dfer.top/think plain
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
class Plain extends Common
{
    protected function configure()
    {
        // 指令配置
        $this->setName('plain')
        ->addArgument('param1', Argument::OPTIONAL, "参数一", '')
        ->addOption('type', 't', Option::VALUE_OPTIONAL, '类型。a：选项一；b：选项二', 'a')
        ->addOption('about', 'a', Option::VALUE_NONE, '简介')
              ->setDescription('控制台脚本。输入`php think plain -h`查看说明');
    }
    
    public function init()
    {
        global $input;
        $param1 = $input->getArgument('param1');
        $about = $input->getOption('about');
        $type = $input->getOption('type');
        if ($about) {
            $this->tp_print("
        | AUTHOR: dfer
        | EMAIL: df_business@qq.com
        | QQ: 3504725309");
            exit();
        }
        if (empty($param1)) {
            $this->tp_print("输入参数一");
            exit();
        }
        
        try {
            switch ($type) {
          case 'a':
           
           break;
          case 'b':
           break;
          default:
           $this->debug_print("类型错误");
           break;
         }
        } catch (Exception $e) {
            $this->debug_print($e->getMessage());
        }
    }
}
