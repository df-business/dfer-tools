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

use think\exception\ValidateException;
use think\{Db, Log, Validate};
use think\facade\{Db as FacadeDb, Log as FacadeLog, Filesystem as FacadeFilesystem, Session as FacadeSession, Cache as FacadeCache, Request as FacadeRequest};
use Dfer\Tools\Constants;

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
        if ($this->checkVersion()) {
            $list = FacadeDb::query("SHOW FULL COLUMNS FROM {$table};");
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
     * 检查版本号
     * tp>=5.1 开始支持facade(静态调用)
     * @param {Object} $need_ver 版本号    比如：Constants::V6
     * @param {Object} $require 必须支持该版本
     **/
    public function checkVersion($need_ver = Constants::V5, $require = false)
    {
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
        if ($this->checkVersion()) {
            FacadeLog::write($data, $identification);
        } else {
            Log::write($data, $identification);
        }
    }

    /**
     *
     * 表单文件上传
     */
    public function uploadForm()
    {
        $this->checkVersion(Constants::V5, true);

        $files = request()->file();
        // dump($files);
        if (!empty($files)) {
            try {
                $result = $this->validate($files, ['image' => 'fileSize:10240|fileExt:jpg|image:200,200,jpg']);
                if ($result !== true) {
                    die($result);
                }

                $files = $files['image'];
                // 多文件上传
                if (\is_array($files)) {
                    $savename = [];
                    foreach ($files as $file) {
                        $savename[] = FacadeFilesystem::putFile('e', $file);
                    }
                }
                // 单文件
                else {
                    // 上传到本地服务器
                    $savename = FacadeFilesystem::putFile('e', $file);
                }
                // dump($savename);
                return $savename;
            } catch (ValidateException $e) {
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
     * @return string(错误信息)|true(成功)
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
        if ($this->checkVersion(Constants::V6)) {
            $result = $v->failException(false)->check($data);
        } else {
            $result = $v->check($data);
        }

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

    // ********************** session START **********************

    /**
     * 根据sessionId进行数据读取
     * @param {Object} $sessionId
     */
    public function sessionRead($sessionId)
    {
        $name = 'sess_' . $sessionId;
        $path = app()->getRuntimePath() . 'session' . DIRECTORY_SEPARATOR . $name;
        if (file_exists($path)) {
            $data = file_get_contents($path);
            if (!empty($data)) {
                return unserialize($data);
            }
        }
        return [];
    }

    /**
     * 根据sessionId进行数据写入
     * @param {Object} $sessionId
     * @param {Object} $data
     */
    public function sessionWrite($sessionId, $data)
    {
        $name = 'sess_' . $sessionId;
        $path = app()->getRuntimePath() . 'session' . DIRECTORY_SEPARATOR . $name;
        if (file_exists($path)) {
            if (!empty($data)) {
                $data = serialize($data);
                return file_put_contents($path, $data);
            } else {
                return unlink($path);
            }
        }
        return false;
    }

    /**
     * 限制登录
     * 网页端，同一个账号，同一时间只能登录一个。不同的浏览器缓存下，最新的登录会让之前的登录状态失效
     * @param {Object} $user_id 用户id
     * @param {Object} $mark 标记
     */
    function loginLimit($user_id, $mark = "")
    {
        $s_id = FacadeSession::getId();
        $mark = $mark ?: FacadeRequest::host();
        $key = "{$mark}.{$user_id}";
        $last_s_id = FacadeCache::store('redis')->get($key);
        // $last_s_id='b1d6625fa8a5d32fff1b53becc7a74bb';
        if ($last_s_id && $last_s_id != $s_id) {
            // 清理上一次登录的用户的登录状态
            $data = $this->sessionRead($last_s_id);
            unset($data['user']);
            $this->sessionWrite($last_s_id, $data);
        }
        FacadeCache::store('redis')->set($key, $s_id);
    }

    // **********************  session END  **********************
}
