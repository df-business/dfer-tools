<?php
declare(strict_types = 1);

namespace Dfer\Tools\TpConsole;

use think\helper\Str;
use think\console\input\{Argument,Option};
use think\exception\ErrorException;
use Exception;
use Dfer\Tools\Statics\Common;

/**
 * +----------------------------------------------------------------------
 * | 用来生成console脚本
 * | eg:
 * | php think create Game
 * | php think create -h
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
class Create extends Command
{
    protected function configure()
    {
        $this->setName('dfer:console_create')->addArgument('name', Argument::OPTIONAL, "脚本名称。命名采用大驼峰", '')
                                      ->addOption('about', 'a', Option::VALUE_NONE, '简介')
                                      ->addOption('type', 't', Option::VALUE_OPTIONAL, '类型。ws(websocket脚本) plain(控制台脚本) ped(php加密、解密)', 'plain')
                                      ->addOption('debug', 'd', Option::VALUE_OPTIONAL, '调试模式', true)
                                      ->setDescription('生成脚本。输入`php think dfer:console_create -h`查看说明');
    }

    public function init()
    {
        try {
            $name = $this->input->getArgument('name');
            $type = $this->input->getOption('type');

            if ($type == 'encode') {
                $name="PhpEncode";
            }
            if (empty($name)) {
                $this->tpPrint("输入类名");
                return;
            }
            switch ($type) {
                 case 'ws':
                  $this->tpPrint("开始生成[websocket脚本]...");
                  break;
                 case 'plain':
                  $this->tpPrint("开始生成[控制台脚本]...");
                  break;
                 case 'ped':
                  $this->tpPrint("开始生成[php加密、解密脚本]...");
                  break;
                 default:
                  $this->tpPrint("类型选择错误");
                  return;
                }
            $name = trim($name);
            // 下划线
            $name_snake=Str::snake($name);
            // 大驼峰
            $name_studly=Str::studly($name);

            $cur_dir = realpath(__DIR__);

            if ($this->is_new_tp) {
                // >=tp6
                $root=app()->getRootPath();

                switch ($type) {
                    case 'plain':
                     $this->fileCreate("{$cur_dir}\Tmpl\PlainCommand.php", "{$root}/app/command/{$name_studly}Command.php", [
                         'namespace Dfer\Tools\TpConsole\Tmpl;'=> "namespace app\command;",
                         'class PlainCommand'                  => "class {$name_studly}Command"
                     ]);
                     // main
                     $this->fileCreate("{$cur_dir}\Tmpl\Plain.php", "{$root}/app/command/{$name_studly}.php", [
                         'namespace Dfer\Tools\TpConsole\Tmpl;'=> "namespace app\command;",
                         'think plain'                         => "think {$name_snake}",
                         'class Plain extends PlainCommand'    => "class {$name_studly} extends {$name_studly}Command",
                         'setName(\'plain\')'                  => "setName('{$name_snake}')"
                     ]);
                     break;

                 case 'ws':
                  $this->fileCreate("{$cur_dir}\Tmpl\WebSocketCommand.php", "{$root}/app/command/{$name_studly}Command.php", [
                    'namespace Dfer\Tools\TpConsole\Tmpl;'=> "namespace app\command;",
                    'class WebSocketCommand'                  => "class {$name_studly}Command"
                  ]);

                  // main
                  $this->fileCreate("{$cur_dir}\Tmpl\WebSocket.php", "{$root}/app/command/{$name_studly}.php", [
                    'namespace Dfer\Tools\TpConsole\Tmpl;'=> "namespace app\command;",
                      'think ws'                            => "think {$name_snake}",
                      'class WebSocket extends WebSocketCommand'=> "class {$name_studly} extends {$name_studly}Command",
                      'setName(\'ws\')'                     => "setName('{$name_snake}')"
                  ]);
                  break;

                 case 'ped':
                  $this->fileCreate("{$cur_dir}\Tmpl\PhpEncryptDecryptCommand.php", "{$root}/app/command/{$name_studly}Command.php", [
                   'namespace Dfer\Tools\TpConsole\Tmpl;'=> "namespace app\command;",
                   'class PhpEncryptDecryptCommand'                  => "class {$name_studly}Command"
                  ]);
                  // main
                  $this->fileCreate("{$cur_dir}\Tmpl\PhpEncryptDecrypt.php", "{$root}/app/command/{$name_studly}.php", [
                   'namespace Dfer\Tools\TpConsole\Tmpl;'=> "namespace app\command;",
                   'think php_encrypt_decrypt'                            => "think {$name_snake}",
                   'class PhpEncryptDecrypt extends PhpEncryptDecryptCommand'=> "class {$name_studly} extends {$name_studly}Command",
                   'setName(\'php_encrypt_decrypt\')'                     => "setName('{$name_snake}')"
                  ]);
                  break;
                 default:
                  break;
                }
                // config
                $this->tpPrint(
<<<STR
生成脚本完成

/app/command/{$name}.php

# console.php
```
<?php
return [
    'dfer:{$name_snake}' => 'app\command\\{$name_studly}'
];

```
- 复制到框架里的console配置文件，比如：`/config/console.php`、`/data/config/console.php`、`/app/user/command.php`
STR);
            } else {
                // 老版本
                $root=ROOT_PATH;
                switch ($type) {
                  case 'game':
                   $this->fileCreate($cur_dir.'\Modules\CommonTmpl.php', $root."/application/api/command/{$module_name}/Common.php", [
                    'namespace Dfer\Tools\Console\Modules;'=> "namespace app\api\command\\{$module_name};",
                       'CommonTmpl'                           => "Common"
                   ]);
                   $this->fileCreate($cur_dir.'\Modules\GameModelTmpl.php', $root."/application/api/command/{$module_name}/GameModel.php", [
                    'namespace Dfer\Tools\Console\Modules;'=> "namespace app\api\command\\{$module_name};",
                       'CommonTmpl'                           => "Common",
                       'GameModelTmpl'                        => "GameModel"
                   ]);
                   // main
                   $this->fileCreate($cur_dir.'\Game.php', $root."/application/api/command/{$name_studly}.php", [
                    'namespace Dfer\Tools\Console;'                => "namespace app\api\command;",
                       'class Game'                                   => "class {$name_studly}",
                       'think game'                                   => "think {$name_snake}",
                       'setName(\'game\')'                            => "setName('{$name_snake}')",
                       '游戏后台'                                         => "ws后台",
                       'use Dfer\Tools\Console\Modules\GameModelTmpl;'=> "use app\api\command\\{$module_name}\GameModel;",
                       'GameModelTmpl'                                => "GameModel"
                   ]);
                   break;

                  case 'plain':
                   $this->fileCreate($cur_dir.'\Modules\CommonTmpl.php', $root."/application/api/command/{$module_name}/Common.php", [
                    'namespace Dfer\Tools\Console\Modules;'=> "namespace app\api\command\\{$module_name};",
                       'CommonTmpl'                           => "Common"
                   ]);
                   $this->fileCreate($cur_dir.'\Modules\PlainModelTmpl.php', $root."/application/api/command/{$module_name}/PlainModel.php", [
                    'namespace Dfer\Tools\Console\Modules;'=> "namespace app\api\command\\{$module_name};",
                       'CommonTmpl'                           => "Common",
                       'PlainModelTmpl'                       => "PlainModel"
                   ]);
                   // main
                   $this->fileCreate($cur_dir.'\Plain.php', $root."/application/api/command/{$name_studly}.php", [
                    'namespace Dfer\Tools\Console;'                 => "namespace app\api\command;",
                       'class Plain'                                   => "class {$name_studly}",
                       'think plain'                                   => "think {$name_snake}",
                       'setName(\'plain\')'                            => "setName('{$name_snake}')",
                       'use Dfer\Tools\Console\Modules\PlainModelTmpl;'=> "use app\api\command\\{$module_name}\PlainModel;",
                       'PlainModelTmpl'                                => "PlainModel"
                   ]);
                   break;

                  case 'encode':
                   $this->fileCreate($cur_dir.'\Modules\CommonTmpl.php', $root."/application/api/command/{$module_name}/Common.php", [
                    'namespace Dfer\Tools\Console\Modules;'=> "namespace app\api\command\\{$module_name};",
                       'CommonTmpl'                           => "Common",
                       '// code'                              => "
  static \$items=[
           'application/admin/controller',
           'application/admin/model',
           'application/api/controller',
           'application/common/controller',
           'application/common/model'
           ];
"
                   ]);
                   $this->fileCreate($cur_dir.'\Modules\PlainModelPhpEncodeTmpl.php', $root."/application/api/command/{$module_name}/PlainModel.php", [
                    'namespace Dfer\Tools\Console\Modules;'=> "namespace app\api\command\\{$module_name};",
                       'CommonTmpl'                           => "Common",
                       'PlainModelPhpEncodeTmpl'              => "PlainModel"
                   ]);
                   // main
                   $this->fileCreate($cur_dir.'\PhpEncode.php', $root."/application/api/command/{$name_studly}.php", [
                    'namespace Dfer\Tools\Console;'                 => "namespace app\api\command;",
                       'use Dfer\Tools\Console\Modules\PlainModelTmpl;'=> "use app\api\command\\{$module_name}\PlainModel;",
                       'PlainModelTmpl'                                => "PlainModel"
                   ]);
                   break;
                  default:
                   # code...
                   break;
                 }
                // config
                Common::configUpdate($root."/application/command.php", [
"
,'app\api\command\\{$name_studly}'
]"
                ]);
                $this->tpPrint("
                 | 生成脚本完成
                 | /application/api/command/{$name_studly}.php
                 | /application/command.php
                 ");
            }
        } catch (ErrorException $e) {
            $this->tpPrint(sprintf("\n%s\n\n%s %s", $e->getMessage(), $e->getFile(), $e->getLine()));
        }catch (Exception $e) {
            $this->tpPrint(sprintf("\n%s\n\n%s %s", $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}
