<?php

namespace Dfer\Tools;

/**
 * +----------------------------------------------------------------------
 * | thinkphp常用的方法
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
class TpCommon
{
    protected static $db;
    protected static $tp_ver;
    protected static $tp_new;
    public function __construct()
    {
        // tp5与tp6调用方式不同
        if (class_exists("\\think\\facade\\Db")) {
            // tp6
            self::$tp_new=true;
            self::$tp_ver=app()->version();
            self::$db = new \think\facade\Db();
        } else {
            // tp5以下
            self::$tp_new=false;
            self::$tp_ver=THINK_VERSION;
            self::$db = new \think\Db();
        }
    }
	
     /**
      * 获取表的字段信息
      * @param {Object} $table 表名
      * @param {Object} keys 返回的字段。支持数组，字符串，为空则返回所有字段
      * @param {Object} $col_name	获取的字段属性，默认是备注
      */
    public function getColName($table,$keys=[], $col_name='Comment')
    {
        $list=self::$db::query("SHOW FULL COLUMNS FROM {$table};");              
     
        $item=[];
        foreach ($list as $key => $value) {
            $item[$value['Field']]=$value[$col_name];
        }
    	
    	if(is_array($keys)){			
    		if(count($keys)==0){
    			return $item;
    		}
    		
    		foreach($keys as $key=>$value){			
    		$data[]=$item[$value];
    		}
    	}
    	else{
    		$data=$item[$keys];
    	}
    	return $data;
    }
	
	/**
	 * 独立日志
	 *
	 * 'apart_level'=>['error','sql','debug','dfer']
	 **/
	public function log($data, $identification='dfer')
	{
	    if (class_exists("\\think\\facade\\Log")) {
	        \think\facade\Log::write($data, $identification);
	    } else {
	        \think\Log::write($data, $identification);
	    }
	}
}
