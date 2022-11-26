<?php
declare(strict_types = 1);

namespace Dfer\Tools\Ws;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

use think\facade\Config;
use think\helper\Str;

use Dfer\Tools\Ws\Modules\CommonBase;
use Dfer\Tools\Ws\Modules\Base;

/**
 * +----------------------------------------------------------------------
 * | 用来生成console脚本
 * | eg:
 * | php think create Game
 * | php think create -h
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
class Create extends Base
{
    const EXPORT=1,IMPORT=2;
    public static $remote;
    public static $local;
    
    protected function configure()
    {
        $this->setName('create')->addArgument('name', Argument::OPTIONAL, "脚本名称。命名采用驼峰法（首字母大写）", '')
                                  ->addOption('about', 'a', Option::VALUE_NONE, '简介')
                                  ->setDescription('生成ws命令');
    }
    
    public function init()
    {
        global $db,$input,$output;
        $CommonBase=new CommonBase();
        try {
            $name = $input->getArgument('name');
            $about = $input->getOption('about');
            if ($about) {
                $CommonBase->print("
          | AUTHOR: dfer
          | EMAIL: df_business@qq.com
          | QQ: 3504725309");
                exit();
            }
                                          
            if (empty($name)) {
                $CommonBase->print("输入类名");
                exit();
            }
            
            $CommonBase->print("开始生成脚本....");
            $name = trim($name);
            //驼峰转下划线
            $name_snake=Str::snake($name);
            // 首字母大写
            $name_title=Str::title($name);

            $module_name=$name_title."Modules";
            $cur_dir = realpath(__DIR__);
            $root=app()->getRootPath();
            
            
            $CommonBase->fileCreate($cur_dir.'\Modules\CommonTmpl.php', $root."/app/command/{$module_name}/Common.php", [
             'namespace Dfer\Tools\Ws\Modules;'=>"namespace app\command\\{$module_name};",
             'CommonTmpl'=>"Common"
            ]);
            
            $CommonBase->fileCreate($cur_dir.'\Modules\GameModel.php', $root."/app/command/{$module_name}/GameModel.php", [
             'namespace Dfer\Tools\Ws\Modules;'=>"namespace app\command\\{$module_name};",
             '# use Dfer\Tools\Ws\Modules\Common;'=>"use Dfer\Tools\Ws\Modules\Common;"
            ]);
            
            // main
            $CommonBase->fileCreate($cur_dir.'\Game.php', $root."/app/command/{$name_title}.php", [
             'namespace Dfer\Tools\Ws;'=>"namespace app\command;",
             'class Game'=>"class {$name_title}",
             'setName(\'game\')'=>"setName('{$name_snake}')",
             '游戏后台'=>"ws后台",
             'use Dfer\Tools\Ws\Modules\GameModel;'=>"use app\command\\{$module_name}\\GameModel;",
             '# use Dfer\Tools\Ws\Modules\Common;'=>"use app\command\\{$module_name}\\Common;"
            ]);
            // config
            $CommonBase->configUpdate($root."/config/console.php", [
             ",'{$name_snake}' => 'app\command\\{$name}'
                ]
             ]"
            ]);
            $CommonBase->print("            
            | 生成脚本完成
            | /app/command/{$name}.php
            | /config/console.php
            ");
        } catch (Exception $e) {
            $CommonBase->print($e->getMessage());
        }
    }
}
