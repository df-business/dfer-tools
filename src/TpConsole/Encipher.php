<?php

/**
 * +----------------------------------------------------------------------
 * | 加密器
 * | 加密类型：phpjm、evals
 * | 对php文件进行加密
 * | 由于程序内部对php的调用方式各异，不建议对tp之类的框架全量加密，只需要对核心的控制器、模板内容进行加密即可
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

namespace Dfer\Tools\TpConsole;

use Dfer\Tools\Common;

class Encipher extends Common
{
    private $c = '';
    private $sourceSrc = '';
    private $sourceSrcList = array();
    private $comments;
    private $targetSrc = '';
    private $writeContent = '';

    private $files = array();

    public function __construct($sourceSrc, $targetSrc, $comments = "")
    {
        if (defined('ROOT_PATH')) {
            $root = ROOT_PATH;
        } else {
            // >=tp6
            $root = app()->getRootPath();
        }

        $this->sourceSrc = $this->formatDirectorySeparator($root . ($sourceSrc ?: ''));
        $this->targetSrc = $this->formatDirectorySeparator($root . ($targetSrc ?: ''));

        $this->colorEcho("$this->sourceSrc >>> $this->targetSrc", 37, 44);
        echo PHP_EOL;

        $this->comments = $comments ?: <<<STR
        /**
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
         */
        STR;

        if (empty($this->sourceSrc) || !file_exists($this->sourceSrc)) {
            exit("源目录(文件)不存在");
        }
        if (empty($this->targetSrc)) {
            exit("目标目录(文件)未设置");
        }

        // 创建目标目录(文件)
        if (is_dir($this->sourceSrc)) {
            // 目录
            if (!file_exists($this->targetSrc)) {
                $this->mkDirs($this->targetSrc);
            }
            $this->sourceSrcList = $this->getSourceList($this->sourceSrc);
        } else {
            // 单个文件
            // 文件不存在则创建
            if (!file_exists($this->targetSrc)) {
                fopen($this->targetSrc, "a+");
            }
        }

        $this->init();
    }

    private function init()
    {
        $this->q1 = "O00O0O"; //base64_decode
        $this->q2 = "O0O000"; //$c(原文经过strtr置换后的密文，由 目标字符+替换字符+base64_encode('原文内容')构成)
        $this->q3 = "O0OO00"; //strtr
        $this->q4 = "OO0O00"; //substr
        $this->q5 = "OO0000"; //52
        $this->q6 = "O00OO0"; //urldecode解析过的字符串（n1zb/ma5\vt0i28-pxuqy*6%6Crkdg9_ehcswo4+f37j）
    }

    /**
     * 获取源目录下的所有文件
     * 根据源目录创建目标目录下的文件夹
     * @param {Object} $path
     */
    private function getSourceList($path)
    {
        // 是文件夹
        if (is_dir($path)) {
            // 获取路径下所有的文件路径
            $files = glob($path . '/*');
            // \var_dump($files);

            foreach ($files as $k => $file) {
                $file = $this->formatDirectorySeparator($file);

                if (is_dir($file)) {
                    // 是文件夹的话，就创建对应文件夹
                    $target = $this->targetSrc . str_replace($this->sourceSrc, '', $file);
                    $this->mkDirs($target);
                    // 循环创建
                    $this->getSourceList($file);
                } else {
                    $this->files[] = $file;
                }
            }
        }
        return $this->files;
    }

    /**
     * 返回随机字符串
     * @return string
     */
    private function createRandKey()
    { // 返回随机字符串
        $str = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
        return str_shuffle($str);
    }

    /**
     * 写入文件
     * @param $targetSrc 写入文件的路径
     * @return $this
     */
    private function write($targetSrc)
    {
        $file = fopen($targetSrc, 'w');
        fwrite($file, $this->writeContent) or die('写文件错误');
        fclose($file);
        return $this;
    }

    /**
     * 对明文内容进行加密处理
     * @param $sourceSrc  要加密的文件路径
     * @return $this
     */
    private function encodeText($sourceSrc)
    {
        //随机密匙1
        $k1 = $this->createRandKey();
        //随机密匙2
        $k2 = $this->createRandKey();
        // 获取源文件内容
        $sourceContent = file_get_contents($sourceSrc);
        //base64加密
        $base64 = base64_encode($sourceContent);
        //根据密匙替换对应字符。
        $c = strtr($base64, $k1, $k2);
        $this->c = $k1 . $k2 . $c;
        return $this;
    }

    private function encodeTemplate()
    {
        $encodeContent = '$' . $this->q6 . '=urldecode("%6E1%7A%62%2F%6D%615%5C%76%740%6928%2D%70%78%75%71%79%2A6%6C%72%6B%64%679%5F%65%68%63%73%77%6F4%2B%6637%6A");$' . $this->q1 . '=$' . $this->q6 . '[3].$' . $this->q6 . '[6].$' . $this->q6 . '[33].$' . $this->q6 . '[30];$' . $this->q3 . '=$' . $this->q6 . '[33].$' . $this->q6 . '[10].$' . $this->q6 . '[24].$' . $this->q6 . '[10].$' . $this->q6 . '[24];$' . $this->q4 . '=$' . $this->q3 . '[0].$' . $this->q6 . '[18].$' . $this->q6 . '[3].$' . $this->q3 . '[0].$' . $this->q3 . '[1].$' . $this->q6 . '[24];$' . $this->q5 . '=$' . $this->q6 . '[7].$' . $this->q6 . '[13];$' . $this->q1 . '.=$' . $this->q6 . '[22].$' . $this->q6 . '[36].$' . $this->q6 . '[29].$' . $this->q6 . '[26].$' . $this->q6 . '[30].$' . $this->q6 . '[32].$' . $this->q6 . '[35].$' . $this->q6 . '[26].$' . $this->q6 . '[30];eval($' . $this->q1 . '("' . base64_encode('$' . $this->q2 . '="' . $this->c . '";eval(\'?>\'.$' . $this->q1 . '($' . $this->q3 . '($' . $this->q4 . '($' . $this->q2 . ',$' . $this->q5 . '*2),$' . $this->q4 . '($' . $this->q2 . ',$' . $this->q5 . ',$' . $this->q5 . '),$' . $this->q4 . '($' . $this->q2 . ',0,$' . $this->q5 . '))));') . '"));';
        $this->writeContent = "<?php" . PHP_EOL . $this->comments . PHP_EOL . $encodeContent . PHP_EOL . "?>";
        return $this;
    }

    /**
     * 获取解密后内容
     * @param $sourceSrcContent 解密前内容
     * @return $this
     */
    private function decodeTemplate($sourceSrcContent)
    {
        //以eval为标志 截取为数组，前半部分为密文中的替换掉的函数名，后半部分为密文
        $m = explode('eval', $sourceSrcContent);
        //对系统函数的替换部分进行执行，得到系统变量
        $varStr = substr($m[0], strpos($m[0], '$'));
        // var_dump($varStr);
        //执行后，后续就可以使用替换后的系统函数名
        eval($varStr);
        //判断是否有密文
        if (!isset($m[1])) {
            return $this;
        }

        //对密文进行截取 {$this->q4}  substr
        $star =  strripos($m[1], '(');
        $end = strpos($m[1], ')');
        $str = ${$this->q4}($m[1], $star, $end);
        //对密文解密 {$this->q1}  base64_decode
        $str = ${$this->q1}($str);
        //截取出解密后的  核心密文
        $evallen = strpos($str, 'eval');
        $str = substr($str, 0, $evallen);
        //执行核心密文 使系统变量被赋予值 $O0O000
        eval($str);
        $this->writeContent = ${$this->q1}(
            ${$this->q3}(
                ${$this->q4}(
                    ${$this->q2},
                    ${$this->q5} * 2
                ),
                ${$this->q4}(
                    ${$this->q2},
                    ${$this->q5},
                    ${$this->q5}
                ),
                ${$this->q4}(
                    ${$this->q2},
                    0,
                    ${$this->q5}
                )
            )
        );
        return $this;
    }

    /**
     * 加密函数
     */
    public function encode()
    {
        // 加密目录下所有文件
        if (is_dir($this->sourceSrc)) {
            $total = 0;
            $copy = 0;
            $encode = 0;
            foreach ($this->sourceSrcList as $k => $source) {
                $target = $this->targetSrc . str_replace($this->sourceSrc, '', $source);
                // \var_dump($target, $this->targetSrc, $this->sourceSrc, $source);
                $ext = $this->getExt($source);
                if ($ext != 'php') {
                    copy($source, $target);
                    echo sprintf("复制文件：{$source}\n{$target}\n-----------------\n");
                    $copy++;
                } else {
                    $this->encodeText($source)->encodeTemplate()->write($target);
                    echo sprintf("加密前文件：{$source}\n加密后文件：{$target}\n-----------------\n");
                    $encode++;
                }
                $total++;
            }
            $rt = sprintf("{$this->sourceSrc}:加密{$encode}个文件，复制{$copy}个文件，共处理{$total}个文件");
            return $rt;
        }
        // 加密单个文件
        else {
            $ext = $this->getExt($this->sourceSrc);
            if ($ext != 'php') {
                echo '不支持这种文件格式';
                return;
            }
            // $this->delFile($this->targetSrc);
            $this->encodeText($this->sourceSrc)->encodeTemplate()->write($this->targetSrc);
            $rt = sprintf("加密前文件：{$this->sourceSrc}\n加密后文件：{$this->targetSrc}\n-----------------\n");
            return $rt;
        }
    }

    /**
     * 解密函数
     */
    public function decode()
    {
        if (is_dir($this->sourceSrc)) {
            $total = 0;
            $copy = 0;
            $encode = 0;
            foreach ($this->sourceSrcList as $k => $source) {
                $target = $this->targetSrc . str_replace($this->sourceSrc, '', $source);
                $ext = $this->getExt($source);
                if ($ext != 'php') {
                    copy($source, $target);
                    echo sprintf("复制文件：{$source}\n{$target}\n-----------------\n");
                    $copy++;
                } else {
                    $sourceSrcContent = file_get_contents($source);
                    $this->decodeTemplate($sourceSrcContent)->write($target);
                    echo sprintf("解密前文件：{$source}\n解密后文件：{$target}\n-----------------\n");
                    $encode++;
                }
                $total++;
            }
            $rt = sprintf("{$this->sourceSrc}:解密{$encode}个文件，复制{$copy}个文件，共处理{$total}个文件");
            return $rt;
        } else {
            $ext = $this->getExt($this->sourceSrc);
            if ($ext != 'php') {
                echo '不支持这种文件格式';
                return;
            }
            $sourceSrcContent = file_get_contents($this->sourceSrc);
            $this->decodeTemplate($sourceSrcContent)->write($this->targetSrc);
            $rt = sprintf("解密前文件：{$this->sourceSrc}\n解密后文件：{$this->targetSrc}\n-----------------\n");
            return $rt;
        }
    }
}
