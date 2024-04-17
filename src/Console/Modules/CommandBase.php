<?php
declare(strict_types = 1);
namespace Dfer\Tools\Console\Modules;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * +----------------------------------------------------------------------
 * | console基础类，继承自Command
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
class CommandBase extends Command
{
    public static $common_base;
    public static $debug;
    public static $db;
    public static $tp_new;
    
    protected function execute(Input $in, Output $out)
    {
        try{
            global $input,$output,$class_src,$common_base,$debug,$db,$tp_new;
            $input=$in;
            $output=$out;
            $class_src=get_class($this);
            self::$common_base=$common_base=new CommonBase();
            self::$debug=$debug = $common_base->objToBool($input->getOption('debug'));
            self::$db=$db;
            self::$tp_new=$tp_new;
            $common_base->debugPrint('程序开始...');
            $this->init();
            $common_base->debugPrint('程序结束');
        }catch (\Exception $exception) {
            $trace_list=[];
            $trace_list[]=$common_base->str("%s %s",[$exception->getFile(),$exception->getLine()]);
            $trace_list[]="";
            foreach($exception->getTrace() as $key=>$value){
                if(empty($value['file']))
                    continue;
                $trace_list[]=$common_base->str("%s %s",[$value['file'],$value['line']]);
            }
            
            $err_msg=str(<<<STR
            ////////////////////////////////////////////////// 出错 START //////////////////////////////////////////////////
            {0}
            
            {1}
            //////////////////////////////////////////////////  出错 END  //////////////////////////////////////////////////
            
            STR,[$exception->getMessage(),implode(PHP_EOL,$trace_list)]);
            
            echo $err_msg;			
        }
    }
}
