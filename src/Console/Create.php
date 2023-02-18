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

use Dfer\Tools\Console\Modules\CommandBase;

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
class Create extends CommandBase
{
    const DEBUG=true;
    const EXPORT=1,IMPORT=2;
    public static $remote;
    public static $local;
    protected function configure()
    {
        $this->setName('dfer_console_create')->addArgument('name', Argument::OPTIONAL, "脚本名称。命名采用驼峰法（首字母大写）", '')
                                      ->addOption('about', 'a', Option::VALUE_NONE, '简介')
                                      ->addOption('type', 't', Option::VALUE_OPTIONAL, '类型。game：websocket脚本；plain：控制台脚本；encode：php加密、解密', 'plain')
                                      ->addOption('debug', 'd', Option::VALUE_OPTIONAL, '调试模式。1:开启;0:关闭', self::DEBUG)
                                      ->setDescription('生成脚本。输入`php think dfer_console_create -h`查看说明');
    }
        
        
    public function init()
    {
        global $db,$input,$output,$tp_new,$common_base,$debug;
            
        try {
            $name = $input->getArgument('name');
            $about = $input->getOption('about');
            $type = $input->getOption('type');
            if ($about) {
                $common_base->tp_print("
              | AUTHOR: dfer
              | EMAIL: df_business@qq.com
              | QQ: 3504725309");
                exit();
            }
            if ($type=='encode') {
                $name="PhpEncode";
            }
            if (empty($name)) {
                $common_base->tp_print("输入类名");
                exit();
            }
            switch ($type) {
                 case 'game':
                  $common_base->tp_print("开始生成[websocket脚本]...");
                  break;
                 case 'plain':
                  $common_base->tp_print("开始生成[控制台脚本]...");
                  break;
                 case 'encode':
                  $common_base->tp_print("开始生成[php加密、解密脚本]...");
                  break;
                 default:
                  $common_base->tp_print("类型选择错误");
                  exit();
                }
            $name = trim($name);
            //驼峰转下划线
            $name_snake=Str::snake($name);
            // 下划线转驼峰(首字母大写)
            $name_title=Str::studly($name);
    
            $module_name=$name_title."Modules";
            $cur_dir = realpath(__DIR__);
                
                
            if ($tp_new) {
                // tp6
                $root=app()->getRootPath();
                switch ($type) {
                 case 'game':
                  $common_base->fileCreate($cur_dir.'\Modules\CommonTmpl.php', $root."/app/command/{$module_name}/Common.php", [
                   'namespace Dfer\Tools\Console\Modules;'=>"namespace app\command\\{$module_name};",
                   'CommonTmpl'=>"Common"
                  ]);
                  
                  $common_base->fileCreate($cur_dir.'\Modules\GameModelTmpl.php', $root."/app/command/{$module_name}/GameModel.php", [
                   'namespace Dfer\Tools\Console\Modules;'=>"namespace app\command\\{$module_name};",
                   'CommonTmpl'=>"Common",
                   'GameModelTmpl'=>"GameModel"
                  ]);
                  
                  // main
                  $common_base->fileCreate($cur_dir.'\Game.php', $root."/app/command/{$name_title}.php", [
                   'namespace Dfer\Tools\Console;'=>"namespace app\command;",
                   'class Game'=>"class {$name_title}",
                   'think game'=>"think {$name_snake}",
                   'setName(\'game\')'=>"setName('{$name_snake}')",
                   '游戏后台'=>"ws后台",
                   'use Dfer\Tools\Console\Modules\GameModelTmpl;'=>"use app\command\\{$module_name}\GameModel;",
                   'GameModelTmpl'=>"GameModel"
                  ]);
                  break;
                 case 'plain':
                  $common_base->fileCreate($cur_dir.'\Modules\CommonTmpl.php', $root."/app/command/{$module_name}/Common.php", [
                   'namespace Dfer\Tools\Console\Modules;'=>"namespace app\command\\{$module_name};",
                   'CommonTmpl'=>"Common"
                  ]);
                  $common_base->fileCreate($cur_dir.'\Modules\PlainModelTmpl.php', $root."/app/command/{$module_name}/PlainModel.php", [
                   'namespace Dfer\Tools\Console\Modules;'=>"namespace app\command\\{$module_name};",
                   'CommonTmpl'=>"Common",
                   'PlainModelTmpl'=>"PlainModel"
                  ]);
                  // main
                  $common_base->fileCreate($cur_dir.'\Plain.php', $root."/app/command/{$name_title}.php", [
                   'namespace Dfer\Tools\Console;'=>"namespace app\command;",
                   'class Plain'=>"class {$name_title}",
                   'think plain'=>"think {$name_snake}",
                   'setName(\'plain\')'=>"setName('{$name_snake}')",
                   'use Dfer\Tools\Console\Modules\PlainModelTmpl;'=>"use app\command\\{$module_name}\PlainModel;",
                   'PlainModelTmpl'=>"PlainModel"
                  ]);
                  break;
                 case 'encode':
                  $common_base->fileCreate($cur_dir.'\Modules\CommonTmpl.php', $root."/app/command/{$module_name}/Common.php", [
                   'namespace Dfer\Tools\Console\Modules;'=>"namespace app\command\\{$module_name};",
                   'CommonTmpl'=>"Common",
                   '// code'=>"static $items=[
           'application/admin/controller',
           'application/admin/model',
           'application/api/controller',
           'application/common/controller',
           'application/common/model'
           ];"
                  ]);
                  $common_base->fileCreate($cur_dir.'\Modules\PlainModelPhpEncodeTmpl.php', $root."/app/command/{$module_name}/PlainModel.php", [
                   'namespace Dfer\Tools\Console\Modules;'=>"namespace app\command\\{$module_name};",
                   'CommonTmpl'=>"Common",
                   'PlainModelPhpEncodeTmpl'=>"PlainModel"
                  ]);
                  // main
                  $common_base->fileCreate($cur_dir.'\PhpEncode.php', $root."/app/command/{$name_title}.php", [
                   'namespace Dfer\Tools\Console;'=>"namespace app\command;",
                   'use Dfer\Tools\Console\Modules\PlainModelTmpl;'=>"use app\command\\{$module_name}\PlainModel;",
                   'PlainModelTmpl'=>"PlainModel"
                  ]);
                  break;
                 default:
                  # code...
                  break;
                }
                // config
                $common_base->configUpdate($root."/config/console.php", [
                 ",'{$name_snake}' => 'app\command\\{$name}'
                    ]
                 ]"
                ]);
                $common_base->tp_print("            
                | 生成脚本完成
                | /app/command/{$name}.php
                | /config/console.php
                ");
            } else {
                // tp5
                $root=ROOT_PATH;
                switch ($type) {
                  case 'game':
                   $common_base->fileCreate($cur_dir.'\Modules\CommonTmpl.php', $root."/application/api/command/{$module_name}/Common.php", [
                    'namespace Dfer\Tools\Console\Modules;'=>"namespace app\api\command\\{$module_name};",
                    'CommonTmpl'=>"Common"
                   ]);
                   $common_base->fileCreate($cur_dir.'\Modules\GameModelTmpl.php', $root."/application/api/command/{$module_name}/GameModel.php", [
                    'namespace Dfer\Tools\Console\Modules;'=>"namespace app\api\command\\{$module_name};",
                    'CommonTmpl'=>"Common",
                    'GameModelTmpl'=>"GameModel"
                   ]);
                   // main
                   $common_base->fileCreate($cur_dir.'\Game.php', $root."/application/api/command/{$name_title}.php", [
                    'namespace Dfer\Tools\Console;'=>"namespace app\api\command;",
                    'class Game'=>"class {$name_title}",
                    'think game'=>"think {$name_snake}",
                    'setName(\'game\')'=>"setName('{$name_snake}')",
                    '游戏后台'=>"ws后台",
                    'use Dfer\Tools\Console\Modules\GameModelTmpl;'=>"use app\api\command\\{$module_name}\GameModel;",
                    'GameModelTmpl'=>"GameModel"
                   ]);
                   break;
                   
                  case 'plain':
                   $common_base->fileCreate($cur_dir.'\Modules\CommonTmpl.php', $root."/application/api/command/{$module_name}/Common.php", [
                    'namespace Dfer\Tools\Console\Modules;'=>"namespace app\api\command\\{$module_name};",
                    'CommonTmpl'=>"Common"
                   ]);
                   $common_base->fileCreate($cur_dir.'\Modules\PlainModelTmpl.php', $root."/application/api/command/{$module_name}/PlainModel.php", [
                    'namespace Dfer\Tools\Console\Modules;'=>"namespace app\api\command\\{$module_name};",
                    'CommonTmpl'=>"Common",
                    'PlainModelTmpl'=>"PlainModel"
                   ]);
                   // main
                   $common_base->fileCreate($cur_dir.'\Plain.php', $root."/application/api/command/{$name_title}.php", [
                    'namespace Dfer\Tools\Console;'=>"namespace app\api\command;",
                    'class Plain'=>"class {$name_title}",
                    'think plain'=>"think {$name_snake}",
                    'setName(\'plain\')'=>"setName('{$name_snake}')",
                    'use Dfer\Tools\Console\Modules\PlainModelTmpl;'=>"use app\api\command\\{$module_name}\PlainModel;",
                    'PlainModelTmpl'=>"PlainModel"
                   ]);
                   break;
                   
                  case 'encode':
                   $common_base->fileCreate($cur_dir.'\Modules\CommonTmpl.php', $root."/application/api/command/{$module_name}/Common.php", [
                    'namespace Dfer\Tools\Console\Modules;'=>"namespace app\api\command\\{$module_name};",
                    'CommonTmpl'=>"Common",
                    '// code'=>"
  static \$items=[
           'application/admin/controller',
           'application/admin/model',
           'application/api/controller',
           'application/common/controller',
           'application/common/model'
           ];
"
                   ]);
                   $common_base->fileCreate($cur_dir.'\Modules\PlainModelPhpEncodeTmpl.php', $root."/application/api/command/{$module_name}/PlainModel.php", [
                    'namespace Dfer\Tools\Console\Modules;'=>"namespace app\api\command\\{$module_name};",
                    'CommonTmpl'=>"Common",
                    'PlainModelPhpEncodeTmpl'=>"PlainModel"
                   ]);
                   // main
                   $common_base->fileCreate($cur_dir.'\PhpEncode.php', $root."/application/api/command/{$name_title}.php", [
                    'namespace Dfer\Tools\Console;'=>"namespace app\api\command;",
                    'use Dfer\Tools\Console\Modules\PlainModelTmpl;'=>"use app\api\command\\{$module_name}\PlainModel;",
                    'PlainModelTmpl'=>"PlainModel"
                   ]);
                   break;
                  default:
                   # code...
                   break;
                 }
                // config
                $common_base->configUpdate($root."/application/command.php", [
"
,'app\api\command\\{$name_title}'
]"
                 ]);
                $common_base->tp_print("            
                 | 生成脚本完成
                 | /application/api/command/{$name_title}.php
                 | /application/command.php
                 ");
            }
        } catch (\think\exception\ErrorException $e) {
            $common_base->tp_print(sprintf("\n%s\n\n%s %s", $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}
