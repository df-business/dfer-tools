<?php

namespace Dfer\Tools\Console;

use think\console\input\Argument;
use think\console\input\Option;

use Dfer\Tools\Console\Modules\PlainModelTmpl;

/**
 * +----------------------------------------------------------------------
 * | 简单控制台
 * | eg:
 * | php think plain
 * | php think plain -t a
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
class Plain extends PlainModelTmpl
{
    const DEBUG=true;
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
        global $input,$common_base,$debug;
        try {
            $debug=self::DEBUG;
            $param1 = $input->getArgument('param1');
            $about = $input->getOption('about');
            $type = $input->getOption('type');
            if ($about) {
                $common_base->tp_print("
        | AUTHOR: dfer
        | EMAIL: df_business@qq.com
        | QQ: 3504725309");
                exit();
            }
            if (empty($param1)) {
                $common_base->tp_print("输入参数一");
                exit();
            }
        
            switch ($type) {
          case 'a':
           
           break;
          case 'b':
           break;
          default:
           $common_base->debug_print("类型错误");
           break;
         }
        } catch (\think\exception\ErrorException $e) {
            $common_base->tp_print(sprintf("\n%s\n\n%s %s", $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}
