<?php

namespace Dfer\Tools\Console;

use think\console\input\Argument;
use think\console\input\Option;

use Dfer\Tools\Console\Modules\PlainModelTmpl;
use Dfer\Tools\Console\Modules\Encipher;

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
 *                 ......    .!%$! ..        | AUTHOR: dfer                             
 *         ......        .;o*%*!  .          | EMAIL: df_business@qq.com                             
 *                .:;;o&***o;.   .           | QQ: 3504725309                             
 *        .;;!o&****&&o;:.    ..        
 * +----------------------------------------------------------------------
 *
 */
class PhpEncode extends PlainModelTmpl
{
    const DEBUG=true;
            
    protected function configure()
    {
        // 指令配置
        $this->setName('php_encode')
                ->addOption('type', 't', Option::VALUE_OPTIONAL, '类型。1：加密；2：解密；3：清理', 1)
                ->addOption('encode_type', 'e', Option::VALUE_OPTIONAL, '类型。1：全量；2：局部；', 2)
                ->addOption('about', 'a', Option::VALUE_NONE, '简介')
                ->addOption('debug', 'd', Option::VALUE_OPTIONAL, '调试模式。1:开启;0:关闭', self::DEBUG)
                ->setDescription('控制台脚本。输入`php think php_encode -h`查看说明');
    }
            
    public function init()
    {
        global $input,$common_base;
        try {
            $type = $input->getOption('type');
            $encode_type = $input->getOption('encode_type');
            $about = $input->getOption('about');
            if ($about) {
                $common_base->tpPrint("
                 | AUTHOR: dfer
                 | EMAIL: df_business@qq.com
                 | QQ: 3504725309");
                exit();
            }
            
            switch ($encode_type) {
             case 1:
              $this->fullEncode($type);
              break;
             case 2:
              $this->partEncode($type);
              break;
             default:
              # code...
              break;
            }
           
        } catch (\think\exception\ErrorException $e) {
            $common_base->tpPrint(sprintf("\n%s\n\n%s %s", $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}
