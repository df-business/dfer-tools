<?php
declare(strict_types = 1);

namespace Dfer\Tools\Console;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

use think\facade\Config;
use think\helper\Str;

use Dfer\Tools\Console\Modules\CommonBase;
use Dfer\Tools\Console\Modules\Base;

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
                                  ->addOption('type', 't', Option::VALUE_OPTIONAL, '类型。game：websocket脚本；console：控制台脚本', 'game')
                                  ->setDescription('生成脚本。输入`php think create -h`查看说明');
    }
    
    public function init()
    {
        global $db,$input,$output;
        $CommonBase=new CommonBase();
        try {
            $name = $input->getArgument('name');
            $about = $input->getOption('about');
            $type = $input->getOption('type');
            if ($about) {
                $CommonBase->tp_print("
          | AUTHOR: dfer
          | EMAIL: df_business@qq.com
          | QQ: 3504725309");
                exit();
            }
                                          
            if (empty($name)) {
                $CommonBase->tp_print("输入类名");
                exit();
            }
            
            $CommonBase->tp_print("开始生成脚本....");
            $name = trim($name);
            //驼峰转下划线
            $name_snake=Str::snake($name);
            // 首字母大写
            $name_title=Str::title($name);

            $module_name=$name_title."Modules";
            $cur_dir = realpath(__DIR__);
            $root=app()->getRootPath();
            
            switch ($type) {
             case 'game':
              $CommonBase->fileCreate($cur_dir.'\Modules\CommonTmpl.php', $root."/app/command/{$module_name}/Common.php", [
               'namespace Dfer\Tools\Console\Modules;'=>"namespace app\command\\{$module_name};",
               'CommonTmpl'=>"Common"
              ]);
              
              $CommonBase->fileCreate($cur_dir.'\Modules\GameModel.php', $root."/app/command/{$module_name}/GameModel.php", [
               'namespace Dfer\Tools\Console\Modules;'=>"namespace app\command\\{$module_name};",
               '# use Dfer\Tools\Console\Modules\Common;'=>"use Dfer\Tools\Console\Modules\Common;"
              ]);
              
              // main
              $CommonBase->fileCreate($cur_dir.'\Game.php', $root."/app/command/{$name_title}.php", [
               'namespace Dfer\Tools\Console;'=>"namespace app\command;",
               'class Game'=>"class {$name_title}",
               'setName(\'game\')'=>"setName('{$name_snake}')",
               '游戏后台'=>"ws后台",
               'use Dfer\Tools\Console\Modules\GameModel;'=>"use app\command\\{$module_name}\\GameModel;",
               '# use Dfer\Tools\Console\Modules\Common;'=>"use app\command\\{$module_name}\\Common;"
              ]);
              break;
             case 'console':
              $CommonBase->fileCreate($cur_dir.'\Modules\CommonTmpl.php', $root."/app/command/{$module_name}/Common.php", [
               'namespace Dfer\Tools\Console\Modules;'=>"namespace app\command\\{$module_name};",
               'CommonTmpl'=>"Common"
              ]);
            
              // main
              $CommonBase->fileCreate($cur_dir.'\Console.php', $root."/app/command/{$name_title}.php", [
               'namespace Dfer\Tools\Console;'=>"namespace app\command;",
               'class Console'=>"class {$name_title}",
               'think console'=>"think {$name_snake}",
               'setName(\'console\')'=>"setName('{$name_snake}')",
               '# use Dfer\Tools\Console\Modules\Common;'=>"use app\command\\{$module_name}\\Common;"
              ]);
              break;
             
             default:
              # code...
              break;
            }
         
            // config
            $CommonBase->configUpdate($root."/config/console.php", [
             ",'{$name_snake}' => 'app\command\\{$name}'
                ]
             ]"
            ]);
            $CommonBase->tp_print("            
            | 生成脚本完成
            | /app/command/{$name}.php
            | /config/console.php
            ");
        } catch (Exception $e) {
            $CommonBase->tp_print($e->getMessage());
        }
    }
}
