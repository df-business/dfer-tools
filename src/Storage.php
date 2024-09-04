<?php

/**
 * +----------------------------------------------------------------------
 * | 本地文件写入存储类
 * |
 * | 借鉴tp3的快速缓存，用最高效的方式操作缓存
 * |
 * | 例：
 * |    // 存储路径为：/data/storage/a/b/1.php
 * |    Storage::fast('a/b/1',[33,22,11]);
 * |    $a=Storage::fast('a/b/1');
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
     * 快速文件数据读取和保存
     * 针对简单类型数据
     * 字符串、数组
     * @param string $name 缓存名称。可以带路径，会自动创建目录
     * @param mixed $value 缓存值。不为空字符串则读取
     * @return mixed
     */
    public function fast($name, $value = '')
    {

        $filename = $this->getPath() . $name . '.php';

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
                $this->put($filename, serialize($value), 'F');
                // 缓存数据
                self::$_cache[$name] = $value;
                return;
            }
        }
        // **********************  写入 END  **********************

        // ********************** 读取 START **********************
        // 获取静态缓存数据，首次从文件读取之后，下次不用再次读取文件
        if (isset(self::$_cache[$name]))
            return self::$_cache[$name];
        // 缓存文件存在
        if ($this->has($filename, 'F')) {
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
    private function getPath()
    {
        $root = dirname(__DIR__, 4);
        $path = "{$root}/data/storage/";
        return $path;
    }
}
