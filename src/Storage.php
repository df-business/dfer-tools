<?php

/**
 * +----------------------------------------------------------------------
 * | 本地文件存储类
 * |
 * | 借鉴tp3的快速缓存、静态缓存，用最高效的方式操作缓存
 * |
 * | 例：
 * |    // 快速缓存存储路径为：/data/storage/a/b/1.php
 * |    Storage::fast('a/b/1',[33,22,11]);
 * |    $a=Storage::fast('a/b/1');
 * |
 * |    // 静态缓存存储路径为：/data/html/
 * |    Storage::readHtml($htmls,null,$this->request->controller(),$this->request->action());
 * |    Storage::writeHtml($content);
 * |
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

use Exception;

class Storage
{
    // 文件内容数组。根据文件名存储文件内容
    private $contents = array();
    // 静态变量存储缓存数据
    static $_cache = array();

    /**
     * 读取静态缓存
     * 在控制器、视图的数据处理之前调用
     * @param Array $htmls 静态规则。格式：actionName=>array('静态规则','缓存时间','附加规则')。如：$rules['index:index'] = array("{$cache_host}/index", $one_hour);
     * @param String $area_name   区域名。如：Home
     * @param String $controller_name   控制器名。如：Index
     * @param String $controller_name   方法名。如：index
     * @param String $tmpl_file   模板文件路径。如：/www/wwwroot/xlx.tye3.com/data/runtime/temp/zh-cn_156bcb23991650fe315687896c88793f.php
     * @return mixed
     **/
    public function readHtml($htmls, $area_name, $controller_name, $action_name, $tmpl_file = null)
    {
        // 无需缓存
        $cacheTime = false;
        // 分析当前的静态规则
        if (!empty($htmls)) {
            $htmls = array_change_key_case($htmls);
            // 检测静态规则
            $controllerName = strtolower($controller_name);
            $actionName     = strtolower($action_name);
            // index:index
            if (isset($htmls["{$controllerName}:{$actionName}"])) {
                // 某个控制器的操作的静态规则
                $html = $htmls["{$controllerName}:{$actionName}"];
            } elseif (isset($htmls["{$controllerName}:"])) {
                // 某个控制器的静态规则
                $html = $htmls["{$controllerName}:"];
            } elseif (isset($htmls[$actionName])) {
                // 所有操作的静态规则
                $html = $htmls[$actionName];
            } elseif (isset($htmls['*'])) {
                // 全局静态规则
                $html = $htmls['*'];
            }
            // 规则不为空
            if (!empty($html)) {
                // 解读静态规则。如：{$cache_host}/index
                $rule = is_array($html) ? $html[0] : $html;
                // 以$_开头的系统变量
                $callback = function ($match) {
                    switch ($match[1]) {
                        case '_GET':
                            $var = $_GET[$match[2]];
                            break;
                        case '_POST':
                            $var = $_POST[$match[2]];
                            break;
                        case '_REQUEST':
                            $var = $_REQUEST[$match[2]];
                            break;
                        case '_SERVER':
                            $var = $_SERVER[$match[2]];
                            break;
                        case '_SESSION':
                            $var = $_SESSION[$match[2]];
                            break;
                        case '_COOKIE':
                            $var = $_COOKIE[$match[2]];
                            break;
                    }
                    return (count($match) == 4) ? $match[3]($var) : $var;
                };
                // {$_变量名.属性名|可选修饰符}。如：{$_user.name}、{$_user.name|lastName}
                $rule = preg_replace_callback('/{\$(_\w+)\.(\w+)(?:\|(\w+))?}/', $callback, $rule);
                // {get参数名|函数名}。如：{type|func}
                $rule = preg_replace_callback('/{(\w+)\|(\w+)}/', function ($match) {
                    return $match[2]($_GET[$match[1]]);
                }, $rule);
                // {get参数名}。如：{type}
                $rule = preg_replace_callback('/{(\w+)}/', function ($match) {
                    return $_GET[$match[1]];
                }, $rule);
                // 特殊系统变量
                $rule = str_ireplace(
                    array('{:area}', '{:controller}', '{:action}'),
                    array($area_name, $controller_name, $action_name),
                    $rule
                );
                // {|函数名}。如：{|func}
                $rule = preg_replace_callback('/{|(\w+)}/', function ($match) {
                    return $match[1]();
                }, $rule);
                // 默认缓存时间（秒）
                $cacheTime = 60;
                if (is_array($html)) {
                    // 自定义函数
                    if (!empty($html[2])) {
                        $rule = $html[2]($rule);
                    }
                    // 缓存有效期
                    $cacheTime = isset($html[1]) ? $html[1] : $cacheTime;
                }
                // 当前缓存文件
                $html_path = $this->getRoot() . "/data/html/";
                // 缓存文件路径
                $cacheFile = "{$html_path}{$rule}.html";
                // 定义全局常量
                define('DFER_HTML_FILE', $cacheFile);

                // 检查静态HTML文件是否有效，如果无效需要重新更新
                //静态文件有效
                $is_valid = true;
                if (!is_file($cacheFile)) {
                    // 缓存不存在，则更新缓存
                    $is_valid = false;
                } elseif (is_file($tmpl_file) && filemtime($tmpl_file) > $this->get($cacheFile, 'mtime')) {
                    // 模板文件存在，且模板文件发生变化，则更新缓存
                    $is_valid = false;
                } elseif (!is_numeric($cacheTime) && function_exists($cacheTime)) {
                    // 通过自定义函数来更新缓存
                    $is_valid = $cacheTime($cacheFile);
                } elseif (0 != $cacheTime && time() > $this->get($cacheFile, 'mtime') + $cacheTime) {
                    // 缓存时间存在，且缓存文件已过期，则更新缓存
                    $is_valid = false;
                }
                // var_dump($cacheTime,$is_valid,time(),$this->get($cacheFile, 'mtime')+ $cacheTime);
                // 静态页面有效
                if (false !== $cacheTime && $is_valid) {
                    // 读取静态页面输出
                    echo $this->read(DFER_HTML_FILE);
                    // 终止当前脚本
                    exit();
                }
                // echo "缓存失效";
            }
        }
    }

    /**
     * 写入静态缓存
     * 在引用“模板缓存”之前，开启“输出缓冲机制"，获取输出缓冲区内容之后调用
     * @param object $content 页面内容（缓冲区内容）
     * @return mixed
     **/
    public function writeHtml($content = null)
    {
        // 如果有HTTP 4xx 3xx 5xx 头部，禁止存储
        if (defined('DFER_HTML_FILE') && !preg_match('/Status.*[345]{1}\d{2}/i', implode(' ', headers_list()))) {
            //静态文件写入
            $this->put(DFER_HTML_FILE, $content);
            // echo "缓存写入";
        }
        return $content;
    }

    /**
     * 快速文件数据读取和保存
     * 针对简单类型数据
     * 字符串、数组
     * @param string $name 缓存名称。可以带路径，会自动创建目录
     * @param mixed $value 缓存值。不为空字符串则读取
     * @return mixed
     */
    public function fast($name, $value = '')
    {
        $filename = $this->getRoot() . "/data/storage/{$name}.php";
        return $this->process($filename, $value);
    }

    /**
     * 本地文件写入、读取
     */
    public function process($filename, $value = '')
    {
        // ********************** 写入 START **********************
        // 缓存值不为空字符串
        if ('' !== $value) {
            // 值为null
            if (is_null($value)) {
                // 名称中有“*”，则返回false
                if (false !== strpos($name, '*')) {
                    return false;
                } else {
                    // 删除缓存文件
                    unset(self::$_cache[$name]);
                    return $this->unlink($filename, 'F');
                }
            } else {
                // 值不为null
                // 写入缓存文件
                $this->put($filename, serialize($value));
                // 缓存数据
                self::$_cache[$name] = $value;
                return true;
            }
        }
        // **********************  写入 END  **********************

        // ********************** 读取 START **********************
        // 获取静态缓存数据，首次从文件读取之后，下次不用再次读取文件
        if (isset(self::$_cache[$name]))
            return self::$_cache[$name];
        // 缓存文件存在
        if ($this->has($filename)) {
            // 读取缓存文件内容，并且反序列化
            $value = unserialize($this->read($filename));
            // 存入静态缓存数据
            self::$_cache[$name] = $value;
        } else {
            // 缓存文件不存在
            $value = false;
        }
        return $value;
        // **********************  读取 END  **********************
    }

    /**
     * 文件内容读取
     * @param string $filename 文件名
     * @return string
     */
    private function read($filename)
    {
        return $this->get($filename, 'content');
    }

    /**
     * 文件是否存在
     * @param string $filename 文件名
     * @return boolean
     */
    private function has($filename)
    {
        return is_file($filename);
    }

    /**
     * 文件写入
     * @param string $filename 文件名
     * @param string $content 文件内容
     * @return boolean
     */
    private function put($filename, $content)
    {
        $dir = dirname($filename);
        // 目录不存在则自动创建目录
        if (!is_dir($dir))
            mkdir($dir, 0755, true);
        if (false === file_put_contents($filename, $content)) {
            throw new Exception("快速存储写入失败");
        } else {
            $this->contents[$filename] = $content;
            return true;
        }
    }

    /**
     * 文件信息读取
     * @param string $filename 文件名
     * @param string $name 信息名 mtime或者content
     * @return boolean
     */
    private function get($filename, $name)
    {
        // 没有读取过文件内容
        if (!isset($this->contents[$filename])) {
            // 不是文件则直接返回
            if (!is_file($filename)) return false;
            // 获取文件内容
            $this->contents[$filename] = file_get_contents($filename);
        }
        // 直接读取变量里的内容
        $content = $this->contents[$filename];
        $info = array(
            'mtime' => filemtime($filename),
            'content' => $content
        );
        return $info[$name];
    }

    /**
     * 文件追加写入
     * @param string $filename 文件名
     * @param string $content 追加的文件内容
     * @return boolean
     */
    private function append($filename, $content)
    {
        if (is_file($filename)) {
            $content = $this->read($filename) . $content;
        }
        return $this->put($filename, $content);
    }

    /**
     * 文件删除
     * @param string $filename 文件名
     * @return boolean
     */
    private function unlink($filename)
    {
        unset($this->contents[$filename]);
        return is_file($filename) ? unlink($filename) : false;
    }

    /**
     * 默认保存路径
     * @return string
     **/
    private function getRoot()
    {
        $root = dirname(__DIR__, 4);
        return $root;
    }
}
