<?php

namespace Dfer\Tools;

/**
 * 常用的方法
 */
class Common
{
    
    
    /**
     * 简介
     *
     * @param Type $var Description
     * @return mixed
     **/
    public static function about()
    {
        $host='http://www.dfer.top';
        header("Location:".$host);
        return $host;
    }
    
    /**
     * 打印
     **/
    public static function print($str=null)
    {
        echo $str.PHP_EOL;
    }
   
    /**
     * 把mysql导出的json文本拼接成数组字符串
     **/
    public static function mysqlJsonToArray($str=null)
    {
		$arr=json_decode($str);
		$item=$arr->RECORDS;
		// var_dump($item);
		
		$name=[];
		foreach ($item as $key => $value) {
		$name[]=$value->name;	
		}		
		$result=sprintf('["%s"]',join('","',$name));
		return $result;
    }
}
