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

use Dfer\Tools\Ws\Modules\Common;

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
class Create extends Common
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
        try {
            $name = $input->getArgument('name');
            $about = $input->getOption('about');
            if ($about) {
                $this->print("
          | AUTHOR: dfer
          | EMAIL: df_business@qq.com
          | QQ: 3504725309");
                exit();
            }
                                          
            if (empty($name)) {
                $this->print("输入类名");
                exit();
            }
            
            $this->print("开始生成脚本....");
            $name = trim($name);
            //驼峰转下划线
            $name_snake=Str::snake($name);
            $module_name=$name."Modules";
            $cur_dir = realpath(__DIR__);
            $root=app()->getRootPath();
            
            
            $this->fileCreate($cur_dir.'\Modules\CommonTmpl.php', $root."/app/command/{$module_name}/Common.php", [
             'namespace Dfer\Tools\Ws\Modules;'=>"namespace app\command\\{$module_name};",
             'CommonTmpl'=>"Common"
            ]);
            
            $this->fileCreate($cur_dir.'\Modules\GameModel.php', $root."/app/command/{$module_name}/GameModel.php", [
             'namespace Dfer\Tools\Ws\Modules;'=>"namespace app\command\\{$module_name};",
             '# use Dfer\Tools\Ws\Modules\Common;'=>"use Dfer\Tools\Ws\Modules\Common;"
            ]);
            
            // main
            $this->fileCreate($cur_dir.'\Game.php', $root."/app/command/{$name}.php", [
             'namespace Dfer\Tools\Ws;'=>"namespace app\command;",
             'class Game'=>"class {$name}",
             'use Dfer\Tools\Ws\Modules\GameModel;'=>"use app\command\\{$module_name}\\GameModel;",
             '# use Dfer\Tools\Ws\Modules\Common;'=>"use app\command\\{$module_name}\\Common;"
            ]);
            // config
            $this->configUpdate($root."/config/console.php", [
             ",'{$name_snake}' => 'app\command\\{$name}'
                ]
             ]"
            ]);
            $this->print("            
            | 生成脚本完成
            | /app/command/{$name}.php
            | /config/console.php
            ");
        } catch (Exception $e) {
            $this->print($e->getMessage());
        }
    }
}
