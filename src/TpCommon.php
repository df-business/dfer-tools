<?php

/**
 * +----------------------------------------------------------------------
 * | thinkphp常用的方法
 * | 对于tp2、3、5、6、8这种大版本迭代，许多功能的开发需要区分对待
 * | tp>=5  https://github.com/top-think/think    https://github.com/top-think/framework    开始支持composer加载框架，以及控制台think命令
 * | tp<5   https://github.com/top-think/thinkphp     只能手动复制框架文件
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

use think\{Validate, Db};

class TpCommon extends Common
{
    // tp版本
    protected $tp_version;

    public function __construct()
    {
        $this->tp_version = $this->getVersion();
    }

    /**
     * 获取表的字段信息
     * @param {Object} $table 表名
     * @param {Object} keys 返回的字段。支持数组，字符串，为空则返回所有字段
     * @param {Object} $col_name    获取的字段属性，默认是备注
     */
    public function getColName($table, $keys = [], $col_name = 'Comment')
    {
        if ($this->facade()) {
            \think\facade\Db::query("SHOW FULL COLUMNS FROM {$table};");
        } else {
            $list = (new Db)->query("SHOW FULL COLUMNS FROM {$table};");
        }

        $item = [];
        foreach ($list as $key => $value) {
            $item[$value['Field']] = $value[$col_name];
        }

        if (is_array($keys)) {
            if (count($keys) == 0) {
                return $item;
            }

            foreach ($keys as $key => $value) {
                $data[] = $item[$value];
            }
        } else {
            $data = $item[$keys];
        }
        return $data;
    }

    /**
     * 静态调用
     * tp>=5.1 开始支持facade
     * @param {Object} $require 必须支持静态调用
     **/
    public function facade($require = false)
    {
        $need_ver = "5.1";
        if ($require) {
            version_compare($this->tp_version, $need_ver, '>=') or die("需要 ThinkPHP >= v{$need_ver} !");
        }

        if ($this->tp_version >= $need_ver) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 日志
     * 独立日志：'apart_level'=>['error','sql','debug','dfer']
     *
     **/
    public function log($data, $identification = 'dfer')
    {
        if ($this->facade()) {
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
        $this->facade(true);

        $files = request()->file();
        // dump($files);
        if (!empty($files)) {
            try {
                $validate = new \app\validate\upload;
                $result = $validate->check($files);
                if (!$result) {
                    echo $validate->getError();
                }

                $files = $files['image'];
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

    /**
     * 获取tp版本
     * @param {Object} $var 变量
     **/
    public function getVersion($var = null)
    {
        // 初始版本
        $tp_version = "0.0.0";

        // 优先通过tp内置方法来获取版本号。
        if (function_exists('app') && method_exists(app(), 'version')) {
            // 从tp5开始支持。从tp8开始，通过composer获取更精确的版本号
            $tp_version = app()->version();
        } elseif (class_exists('\think\App') && defined('\think\App::VERSION')) {
            // 从tp5开始支持，每代tp的App类都会携带VERSION常量
            $tp_version = \think\App::VERSION;
        } elseif (defined('THINK_VERSION')) {
            // 从tp2开始支持，到tp5被移除
            $tp_version = THINK_VERSION;
        }
        // var_dump($tp_version,$tp_version>="5.1.0",class_exists('\think\App'),defined('\think\App::VERSION'),function_exists('app')&&method_exists(app(), 'version'));
        return $tp_version;
    }
}
