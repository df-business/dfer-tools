<?php

namespace Dfer\Tools\TpConsole;

use think\helper\Str;
use think\console\input\{Argument, Option};
use think\exception\ErrorException;
use Exception;
use Dfer\Tools\Statics\Common;

/**
 * +----------------------------------------------------------------------
 * | 用来生成console脚本
 * | eg:
 * | php think dfer:console_create test -t service
 * | php think dfer:console_create -h
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
            ->addOption('type', 't', Option::VALUE_OPTIONAL, '类型。ws(websocket脚本) plain(控制台脚本) ped(php加密、解密) service(linux自启动服务)', 'plain')
            ->addOption('debug', 'd', Option::VALUE_OPTIONAL, '调试模式', true)
            ->addOption('code', 'c', Option::VALUE_OPTIONAL, 'service里的代码，不设置时调用默认的sh文件。例如：php think dfer:console_create upload -t service -c "php /www/wwwroot/www.dfer.site/think dfer:upload"')
            ->setDescription('生成脚本。输入`php think dfer:console_create -h`查看说明');
    }

    public function init()
    {
        try {
            $name = $this->input->getArgument('name');
            $type = $this->input->getOption('type');

            if ($type == 'encode') {
                $name = "PhpEncode";
            }
            if (empty($name)) {
                // 获取所有参数
                // var_dump($this->getNativeDefinition()->getArguments());
                $this->output->describe($this);
                return;
            }
            switch ($type) {
                case 'ws':
                    $this->tpPrint("生成[websocket脚本]");
                    break;
                case 'plain':
                    $this->tpPrint("生成[控制台脚本]");
                    break;
                case 'ped':
                    $this->tpPrint("生成[php加密、解密脚本]");
                    break;
                case 'service':
                    $this->tpPrint("生成[linux服务脚本]");
                    break;

                default:
                    $this->tpPrint("类型选择错误");
                    return;
            }
            $name = trim($name);
            // 下划线
            $name_snake = Str::snake($name);
            // 大驼峰
            $name_studly = Str::studly($name);

            switch ($type) {
                case 'plain':
                    $this->fileCreate("{$this->cur_dir}\Tmpl\PlainCommand.php", "{$this->root_commond}/{$name_studly}Command.php", [
                        'namespace Dfer\Tools\TpConsole\Tmpl;' => "namespace app\command;",
                        'class PlainCommand'                  => "class {$name_studly}Command"
                    ]);
                    // main
                    $this->fileCreate("{$this->cur_dir}\Tmpl\Plain.php", "{$this->root_commond}/{$name_studly}.php", [
                        'namespace Dfer\Tools\TpConsole\Tmpl;' => "namespace app\command;",
                        'think plain'                         => "think {$name_snake}",
                        'class Plain extends PlainCommand'    => "class {$name_studly} extends {$name_studly}Command",
                        'setName(\'plain\')'                  => "setName('{$name_snake}')"
                    ]);
                    break;

                case 'ws':
                    $this->fileCreate("{$this->cur_dir}\Tmpl\WebSocketCommand.php", "{$this->root_commond}/{$name_studly}Command.php", [
                        'namespace Dfer\Tools\TpConsole\Tmpl;' => "namespace app\command;",
                        'class WebSocketCommand'                  => "class {$name_studly}Command"
                    ]);

                    // main
                    $this->fileCreate("{$this->cur_dir}\Tmpl\WebSocket.php", "{$this->root_commond}/{$name_studly}.php", [
                        'namespace Dfer\Tools\TpConsole\Tmpl;' => "namespace app\command;",
                        'think ws'                            => "think {$name_snake}",
                        'class WebSocket extends WebSocketCommand' => "class {$name_studly} extends {$name_studly}Command",
                        'setName(\'ws\')'                     => "setName('{$name_snake}')"
                    ]);
                    break;

                case 'ped':
                    $this->fileCreate("{$this->cur_dir}\Tmpl\PhpEncryptDecryptCommand.php", "{$this->root_commond}/{$name_studly}Command.php", [
                        'namespace Dfer\Tools\TpConsole\Tmpl;' => "namespace app\command;",
                        'class PhpEncryptDecryptCommand'                  => "class {$name_studly}Command"
                    ]);
                    // main
                    $this->fileCreate("{$this->cur_dir}\Tmpl\PhpEncryptDecrypt.php", "{$this->root_commond}/{$name_studly}.php", [
                        'namespace Dfer\Tools\TpConsole\Tmpl;' => "namespace app\command;",
                        'think php_encrypt_decrypt'                            => "think {$name_snake}",
                        'class PhpEncryptDecrypt extends PhpEncryptDecryptCommand' => "class {$name_studly} extends {$name_studly}Command",
                        'setName(\'php_encrypt_decrypt\')'                     => "setName('{$name_snake}')"
                    ]);
                    break;
                case 'service':
                    $code = $this->input->getOption('code');
                    $this->fileCreate("{$this->cur_dir}\Tmpl\autorun.service", "{$this->root_commond}/dfer_{$name_snake}.service", [
                        '[Description]' => "{$name_snake}",
                        '[ExecStart]' => $code ?: "{$this->root_commond}/dfer_{$name_snake}.sh",
                    ]);
                    if (!$code) {
                        $this->fileCreate("{$this->cur_dir}\Tmpl\autorun.sh", "{$this->root_commond}/dfer_{$name_snake}.sh", []);
                        chmod("{$this->root_commond}/dfer_{$name_snake}.sh", 0777);
                    }
                    return $this->tpPrint(
                        <<<STR

                          生成服务配置文件完成：{$this->root_commond}/dfer_{$name_snake}.service

                          生成服务脚本文件完成：{$this->root_commond}/dfer_{$name_snake}.sh

                          # 终端命令
                          ```
                          \cp {$this->root_commond}/dfer_{$name_snake}.service /usr/lib/systemd/system

                          sudo systemctl disable dfer_{$name_snake}
                          sudo systemctl enable dfer_{$name_snake}
                          sudo systemctl daemon-reload
                          sudo systemctl stop dfer_{$name_snake}
                          sudo systemctl start dfer_{$name_snake}
                          sudo systemctl restart dfer_{$name_snake}
                          sudo systemctl status dfer_{$name_snake}
                          ```
                          - 在linux终端运行上述代码，添加、管理自定义服务
                          - 建议保存上述命令，方便后续管理
                        STR
                    );
                    break;
                default:
                    break;
            }
            if ($this->is_new_tp) {
                return $this->tpPrint(<<<STR

                    生成脚本完成：/app/command/{$name}.php

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
                return $this->tpPrint(<<<STR

                    生成脚本完成：/application/api/command/{$name}.php

                    # console.php
                    ```
                    <?php
                    return [
                        'dfer:{$name_snake}' => 'app\api\command\\{$name_studly}'
                    ];

                    ```
                    - 复制到框架里的console配置文件，比如：`/config/console.php`、`/data/config/console.php`、`/app/user/command.php`

                    STR);
            }
        } catch (ErrorException $e) {
            $this->tpPrint(sprintf("\n%s\n\n%s %s", $e->getMessage(), $e->getFile(), $e->getLine()));
        } catch (Exception $e) {
            $this->tpPrint(sprintf("\n%s\n\n%s %s", $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}
