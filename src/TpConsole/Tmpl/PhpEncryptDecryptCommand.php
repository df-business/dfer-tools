<?php

namespace Dfer\Tools\TpConsole\Tmpl;

use Dfer\Tools\TpConsole\Command;
use Dfer\Tools\TpConsole\Encipher;
use Dfer\Tools\Statics\Common;

/**
 * +----------------------------------------------------------------------
 * | 普通console类模板
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
class PhpEncryptDecryptCommand extends Command
{
    protected $items = [
        'application/admin/controller',
        'application/admin/model',
        'application/api/controller',
        'application/common/controller',
        'application/common/model'
    ];

    /**
     * 全量加密
     * 对特定目录加密，其余原样复制项目内容
     * @param {Object} $type
     */
    public function fullEncode($type)
    {
        // 从项目根目录开始获取路径。不要以斜杠结尾，执行成功之后会在同级建立'e'后缀的加密目录或者'd'后缀的解密目录
        $rt = [];
        switch ($type) {
            case 1:
                // 加密
                $path = '';
                $target = '.e';
                $encipher = new Encipher($path, $target);
                $rt[] = $encipher->encode();
                break;
            case 2:
                // 解密
                $path = '.e';
                $target = '.d';
                $encipher = new Encipher($path, $target);
                $rt[] = $encipher->decode();
                break;
            case 3:
                // 清理
                if (Common::delDir('.e/')) {
                    $rt[] = sprintf("已删除加密目录\n");
                }
                if (Common::delDir('.d/')) {
                    $rt[] = sprintf("已删除解密目录\n");
                }
                break;
            default:
                $this->debugPrint("类型错误");
                break;
        }
        echo PHP_EOL;
        echo
        <<<STR
           　　┏┓　　　┏┓+ +
           　┏┛┻━━━┛┻┓ + +
           　┃　　　　　　　┃ 　
           　┃　　　━　　　┃ ++ + + +
            ████━████ ┃+
           　┃　　　　　　　┃ +
           　┃　　　┻　　　┃
           　┃　　　　　　　┃ + +
           　┗━┓　　　┏━┛
           　　　┃　　　┃　　　　　　　　　　　
           　　　┃　　　┃ + + + +
           　　　┃　　　┃
           　　　┃　　　┃ +
           　　　┃　　　┃
           　　　┃　　　┃　　+　　　　　　　　　
           　　　┃　 　　┗━━━┓ + +
           　　　┃ 　　　　　　　┣┓
           　　　┃ 　　　　　　　┏┛
           　　　┗┓┓┏━┳┓┏┛ + + + +
           　　　　┃┫┫　┃┫┫
           　　　　┗┻┛　┗┻┛+ + + +
        STR;
        echo PHP_EOL;
        foreach ($rt as $key => $value) {
            Common::colorEcho("$value", 37, 44);
        }
    }

    /**
     * 局部加密
     * 针对特定文件夹的php文件加密，在同级目录生成加密目录
     * @param {Object} $type
     */
    public function partEncode($type)
    {
        // 从项目根目录开始获取路径。不要以斜杠结尾，执行成功之后会在同级建立'e'后缀的加密目录或者'd'后缀的解密目录
        $items = $this->items;
        $rt = [];
        switch ($type) {
            case 1:
                // 加密
                foreach ($items as $key => $value) {
                    $path = $value;
                    $target = '.e/' . $value;
                    $encipher = new Encipher($path, $target);
                    $rt[] = $encipher->encode();
                }
                break;
            case 2:
                // 解密
                foreach ($items as $key => $value) {
                    $path = '.e/' . $value;
                    $target = '.d/' . $value;
                    $encipher = new Encipher($path, $target);
                    $rt[] = $encipher->decode();
                }
                break;
            case 3:
                // 清理
                $files = new \Dfer\Tools\Files;
                foreach ($items as $key => $value) {
                    $path_e = '.e/' . $value;
                    $path_d = '.d/' . $value;

                    if (Common::delDir($path_e)) {
                        $rt[] = sprintf("已删除{$value}的加密文件\n");
                    }
                    if (Common::delDir($path_d)) {
                        $rt[] = sprintf("已删除{$value}的解密文件\n");
                    }
                }
                if (Common::delDir('.e/')) {
                    $rt[] = sprintf("已删除加密目录\n");
                }
                if (Common::delDir('.d/')) {
                    $rt[] = sprintf("已删除解密目录\n");
                }
                break;
            default:
                $this->debugPrint("类型错误");
                break;
        }
        echo PHP_EOL;
        echo
        <<<STR
           　　┏┓　　　┏┓+ +
           　┏┛┻━━━┛┻┓ + +
           　┃　　　　　　　┃ 　
           　┃　　　━　　　┃ ++ + + +
            ████━████ ┃+
           　┃　　　　　　　┃ +
           　┃　　　┻　　　┃
           　┃　　　　　　　┃ + +
           　┗━┓　　　┏━┛
           　　　┃　　　┃　　　　　　　　　　　
           　　　┃　　　┃ + + + +
           　　　┃　　　┃
           　　　┃　　　┃ +
           　　　┃　　　┃
           　　　┃　　　┃　　+　　　　　　　　　
           　　　┃　 　　┗━━━┓ + +
           　　　┃ 　　　　　　　┣┓
           　　　┃ 　　　　　　　┏┛
           　　　┗┓┓┏━┳┓┏┛ + + + +
           　　　　┃┫┫　┃┫┫
           　　　　┗┻┛　┗┻┛+ + + +
        STR;
        echo PHP_EOL;
        foreach ($rt as $key => $value) {
            Common::colorEcho("$value", 37, 44);
        }
    }
}
