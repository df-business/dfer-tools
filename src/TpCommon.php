<?php
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
 *                 ......    .!%$! ..     | AUTHOR: dfer
 *         ......        .;o*%*!  .       | EMAIL: df_business@qq.com
 *                .:;;o&***o;.   .        | QQ: 3504725309
 *        .;;!o&****&&o;:.    ..          | WEBSITE: http://www.dfer.site
 * +----------------------------------------------------------------------
 *
 */
namespace Dfer\Tools;

use think\Validate;

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
      * @param {Object} $col_name    获取的字段属性，默认是备注
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


    /**
     *
     * 表单文件上传
     */
    public function uploadForm()
    {
        $files=request()->file();
        // dump($files);
        if (!empty($files)) {
            try {
                $validate = new \app\validate\upload;
                $result = $validate->check($files);
                if (!$result) {
                    echo $validate->getError();
                }

                $files=$files['image'];
                // 多文件上传
                if (\is_array($files)) {
                    $savename = [];
                    foreach ($files as $file) {
                        $savename[] = \think\facade\Filesystem::putFile('e', $file);
                    }
                }
                // 单文件
                else {
                    // 上传到本地服务器
                    $savename = \think\facade\Filesystem::putFile('e', $file);
                }
                // dump($savename);
                return $savename;
            } catch (\think\exception\ValidateException $e) {
                dump($e);
                return $e;
            }
        }
    }

    /**
     * 验证数据
     * @param array        $data     数据
     * @param string|array $validate 验证器对象字符串
     * @param array        $message  提示信息
     * @param bool         $batch    是否批量验证
     * @param mixed        $callback 回调方法（闭包）
     * @return array|string|true
     * @throws ValidateException
     */
    public function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = $validate;
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch) {
            $v->batch(true);
        }

        $result = $v->failException(false)->check($data);

        if (!$result) {
            $result = $v->getError();
        }

        return $result;

    }

}
