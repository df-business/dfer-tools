<?php

namespace Dfer\Tools;

/**
 * +----------------------------------------------------------------------
 * | thinkphp常用的方法
 * +----------------------------------------------------------------------
 *       .::::.
 *     .::::::::.            | AUTHOR: dfer
 *     :::::::::::           | EMAIL: df_business@qq.com
 *  ..:::::::::::'           | QQ: 3504725309
 *             '::::::::::::'
 * .::::::::::
 *           '::::::::::::::..
 * ..::::::::::::.
 *              ``::::::::::::::::
 *::::``:::::::::'        .:::.
 *              ::::'   ':::::'       .::::::::.
 *            .::::'      ::::     .:::::::'::::.
 *           .:::'       :::::  .:::::::::' ':::::.
 *          .::'        :::::.:::::::::'      ':::::.
 *         .::'         ::::::::::::::'         ``::::.
 *     ...:::           ::::::::::::'              ``::.
 *   ```` ':.          ':::::::::'   ::::..
 *       '.:::::'     ':'````..
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
     **/
    public function getColName($table, $type=1)
    {
        $data=self::$db::query("SHOW FULL COLUMNS FROM {$table};");
        
        switch ($type) {
         case 1:
         // 备注
          $col_name='Comment';
          break;
         default:
         // 字段名
          $col_name='Field';
          break;
        }
     
        $item=[];
        foreach ($data as $key => $value) {
            $item[]=$value[$col_name];
        }
        return $item;
    }
}
