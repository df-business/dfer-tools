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
   
}
