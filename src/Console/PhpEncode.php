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
class PhpEncode extends PlainModelTmpl
{
    const DEBUG=true;
        
    protected function configure()
    {
        // 指令配置
        $this->setName('php_encode')
            ->addOption('type', 't', Option::VALUE_OPTIONAL, '类型。1:加密;2:解密;3:清理', 1)
            ->addOption('about', 'a', Option::VALUE_NONE, '简介')
            ->addOption('debug', 'd', Option::VALUE_OPTIONAL, '调试模式。1:开启;0:关闭', self::DEBUG)
            ->setDescription('控制台脚本。输入`php think php_encode -h`查看说明');
    }
        
    public function init()
    {
        global $input,$common_base;
        try {
            $type = $input->getOption('type');
            $about = $input->getOption('about');
            if ($about) {
                $common_base->tp_print("
             | AUTHOR: dfer
             | EMAIL: df_business@qq.com
             | QQ: 3504725309");
                exit();
            }
            // 从项目根目录开始获取路径。不要以斜杠结尾，执行成功之后会在同级建立'e'后缀的加密目录或者'd'后缀的解密目录
            $items=[
                'application/admin/controller',
                'application/admin/model',
                'application/api/controller',
                'application/common/controller',
                'application/common/model'
                ];
            $rt=[];
            switch ($type) {
                 case 1:
                   foreach ($items as $key => $value) {
                       $path = $value;
                       $target = $value.'.e';
                       $encipher = new Encipher($path, $target);
                       $rt[]=$encipher->encode();
                   }
                  break;
                 case 2:
                  foreach ($items as $key => $value) {
                      $path = $value.'.e';
                      $target = $value.'.d';
                      $encipher = new Encipher($path, $target);
                      $rt[]=$encipher->decode();
                  }
                  break;
                 case 3:
                  $files = new \Dfer\Tools\Files;
                  foreach ($items as $key => $value) {
                      $path_e = $value.'.e';
                      $path_d = $value.'.d';
                      
                      if ($files->delDir($path_e)&&$files->delDir($path_d)) {
                          $rt[]=\sprintf("已删除{$value}的加密、解密文件\n");
                      }
                  }
                  break;
                 default:
                  $common_base->debug_print("类型错误");
                  break;
                }
            echo "\n
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
                \n";
            foreach ($rt as $key => $value) {
                echo $value;
            }
        } catch (\think\exception\ErrorException $e) {
            $common_base->tp_print(sprintf("\n%s\n\n%s %s", $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}
