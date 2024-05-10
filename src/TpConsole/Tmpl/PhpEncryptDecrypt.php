<?php
namespace Dfer\Tools\TpConsole\Tmpl;

use think\console\input\{Argument,Option};
use think\exception\ErrorException;

/**
 * +----------------------------------------------------------------------
 * | PHP加密、解密
 * | eg:
 * | php think php_encode
 * | php think php_encode -t a
 * | php /www/wwwroot/xxx.dfer.top/think php_encode
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
class PhpEncryptDecrypt extends PhpEncryptDecryptCommand
{
    // 自定义需要进行处理的文件所属目录（相对于项目根目录）
    protected $items=[
    'test'
    ];

    protected function configure()
    {
        // 指令配置
        $this->setName('php_encrypt_decrypt')
                ->addOption('type', 't', Option::VALUE_REQUIRED, '类型。1 加密    2 解密    3 清理', 1)
                ->addOption('range', 'e', Option::VALUE_REQUIRED, '范围。1 全量    2 局部', 2)
                ->addOption('about', 'a', Option::VALUE_NONE, '简介')
                ->addOption('debug', 'd', Option::VALUE_REQUIRED, '调试模式。1 开启    0 关闭', true)
                ->setDescription('控制台脚本。输入`php think php_encrypt_decrypt -h`查看说明');
    }

    public function init()
    {
        try {
            $type = $this->input->getOption('type');
            $range = $this->input->getOption('range');

            switch ($range) {
             case 1:
              $this->fullEncode($type);
              break;
             case 2:
              $this->partEncode($type);
              break;
             default:
              break;
            }
        } catch (ErrorException $e) {
            $this->tpPrint(sprintf("\n%s\n\n%s %s", $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}
