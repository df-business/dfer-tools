<?php

namespace Dfer\Tools\TpConsole\Tmpl;

use think\console\input\{Argument, Option};
use think\exception\ErrorException;

/**
 * +----------------------------------------------------------------------
 * | 简单控制台
 * | eg:
 * | php think plain
 * | php think plain -t a
 * | php /www/wwwroot/xxx.dfer.top/think plain
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
class Plain extends PlainCommand
{

    protected function configure()
    {
        parent::configure();
        $this->setName('plain')
            ->addArgument('param1', Argument::OPTIONAL, "参数一", '')
            ->addOption('type', 't', Option::VALUE_OPTIONAL, '类型。a：选项一；b：选项二', 'a')
            ->addOption('about', 'a', Option::VALUE_NONE, '简介')
            ->addOption('debug', 'd', Option::VALUE_REQUIRED, '调试模式。1:开启;0:关闭', true)
            ->setDescription('控制台脚本。输入`php think plain -h`查看说明');
    }

    public function init()
    {
        try {
            $param1 = $this->input->getArgument('param1');
            $type = $this->input->getOption('type');

            if (empty($param1)) {
                $this->tpPrint("输入参数一");
                $this->output->describe($this);
                return;
            }
            $this->tpPrint("输入:{$param1}");
            switch ($type) {
                case 'a':
                    break;
                case 'b':
                    break;
                default:
                    $this->debugPrint("类型错误");
                    break;
            }
        } catch (ErrorException $e) {
            $this->tpPrint(sprintf("\n%s\n\n%s %s", $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}
