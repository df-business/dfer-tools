<?php
namespace Dfer\Tools\Console\Modules;

/**
 * +----------------------------------------------------------------------
 * | 加密器
 * |
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
 *                 ......    .!%$! ..        | AUTHOR: dfer                             
 *         ......        .;o*%*!  .          | EMAIL: df_business@qq.com                             
 *                .:;;o&***o;.   .           | QQ: 3504725309                             
 *        .;;!o&****&&o;:.    ..        
 * +----------------------------------------------------------------------
 *
 */
class Encipher
{
    private $c = '';
    private $_sourceFile = '';
    private $_sourceFileArray = array();
    private $_targetFile = '';
    private $_writeContent = '';
    private $_comments = array(
        'Author: Dfer',
        'Email: df_business@qq.com'
    );
    protected $files = null;
    public function __construct($sourceSrc, $targetSrc, $comments = array())
    {
        $this -> files = new \Dfer\Tools\Files;
        
        $root=ROOT_PATH;
        self::$arr = array();
        $sourceFile=$root.$sourceSrc;
        // $root = app()->getRootPath();
        $targetFile=$root.$targetSrc;
        
        // var_dump($sourceFile,$targetFile);
        // die();
     
        !empty($sourceFile) && $this->_sourceFile = $sourceFile;
        !empty($targetFile) && $this->_targetFile = $targetFile;
        !empty($comments) && $this->comments = (array)$comments;

        if (empty($this->_sourceFile) || !file_exists($this->_sourceFile)) {
            exit("源目录不存在");
        }
        if (empty($this->_targetFile)) {
            exit("目标目录未设置");
        }
        
        
        // 源文件是目录
        if (is_dir($this->_sourceFile)) {
            if (!file_exists($this->_targetFile)) {
                $this->mkDirs($this->_targetFile);
            }
            $this->_sourceFileArray = $this->getSourceFile($this->_sourceFile);
        } else {
            if (!file_exists($this->_targetFile)) {
                //如果目标目录不存在，则创建
                fopen($this->_targetFile, "a+");
            }
        }
        
        
        
        $this->init();
    }

    private function init()
    {
        $this->q1 = "O00O0O";//base64_decode
        $this->q2 = "O0O000";//$c(原文经过strtr置换后的密文，由 目标字符+替换字符+base64_encode('原文内容')构成)
        $this->q3 = "O0OO00";//strtr
        $this->q4 = "OO0O00";//substr
        $this->q5 = "OO0000";//52
        $this->q6 = "O00OO0";//urldecode解析过的字符串（n1zb/ma5\vt0i28-pxuqy*6%6Crkdg9_ehcswo4+f37j）
    }
    public static $arr = array();
    /**
     * 获取目录下的所有文件
     * @param {Object} $path
     */
    private function getSourceFile($path)
    {
        // 是文件夹
        if (is_dir($path)) {
            // 获取路径下所有的文件路径
            $array = glob($path . '/*');
            // \var_dump($array);
            
            foreach ($array as $k => $v) {
                // 是文件夹的话，就创建对应文件夹
                if (is_dir($v)) {
                    $target = $this->_targetFile.str_replace($this->_sourceFile, '', $v);
                    $this->mkDirs($target);
                    // 循环创建
                    $this->getSourceFile($v);
                } else {
                    self::$arr[] = $v;
                }
            }
        }
        return self::$arr;
    }

    /**
     * 递归创建目录
     * @param $dir
     * @return bool
     */
    private function mkDirs($dir)
    {
        if (!is_dir($dir)) {
            if (!$this->mkDirs(dirname($dir))) {
                return false;
            }
            if (!mkdir($dir, 0777)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 返回随机字符串
     * @return string
     */
    private function createRandKey()
    { // 返回随机字符串
        $str = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        return str_shuffle($str);
    }

    /**
     * 写入文件
     * @param $targetFile 写入文件的路径
     * @return $this
     */
    private function write($targetFile)
    {
        $file = fopen($targetFile, 'w');
        fwrite($file, $this->_writeContent) or die('写文件错误');
        fclose($file);
        return $this;
    }

    /**
     * 对明文内容进行加密处理
     * @param $sourceFile  要加密的文件路径
     * @return $this
     */
    private function encodeText($sourceFile)
    {
        //随机密匙1
        $k1 = $this->createRandKey();
        //随机密匙2
        $k2 = $this->createRandKey();
        // 获取源文件内容
        $sourceContent = file_get_contents($sourceFile);
        //base64加密
        $base64 = base64_encode($sourceContent);
        //根据密匙替换对应字符。
        $c = strtr($base64, $k1, $k2);
        $this->c = $k1 . $k2 . $c;
        return $this;
    }

    private function encodeTemplate()
    {
        $encodeContent = '$' . $this->q6 . '=urldecode("%6E1%7A%62%2F%6D%615%5C%76%740%6928%2D%70%78%75%71%79%2A6%6C%72%6B%64%679%5F%65%68%63%73%77%6F4%2B%6637%6A");$' . $this->q1 . '=$' . $this->q6 . '{3}.$' . $this->q6 . '{6}.$' . $this->q6 . '{33}.$' . $this->q6 . '{30};$' . $this->q3 . '=$' . $this->q6 . '{33}.$' . $this->q6 . '{10}.$' . $this->q6 . '{24}.$' . $this->q6 . '{10}.$' . $this->q6 . '{24};$' . $this->q4 . '=$' . $this->q3 . '{0}.$' . $this->q6 . '{18}.$' . $this->q6 . '{3}.$' . $this->q3 . '{0}.$' . $this->q3 . '{1}.$' . $this->q6 . '{24};$' . $this->q5 . '=$' . $this->q6 . '{7}.$' . $this->q6 . '{13};$' . $this->q1 . '.=$' . $this->q6 . '{22}.$' . $this->q6 . '{36}.$' . $this->q6 . '{29}.$' . $this->q6 . '{26}.$' . $this->q6 . '{30}.$' . $this->q6 . '{32}.$' . $this->q6 . '{35}.$' . $this->q6 . '{26}.$' . $this->q6 . '{30};eval($' . $this->q1 . '("' . base64_encode('$' . $this->q2 . '="' . $this->c . '";eval(\'?>\'.$' . $this->q1 . '($' . $this->q3 . '($' . $this->q4 . '($' . $this->q2 . ',$' . $this->q5 . '*2),$' . $this->q4 . '($' . $this->q2 . ',$' . $this->q5 . ',$' . $this->q5 . '),$' . $this->q4 . '($' . $this->q2 . ',0,$' . $this->q5 . '))));') . '"));';
        $headers = array_map('trim', array_merge(array('/*'), $this->_comments, array('*/')));
        $this->_writeContent = "<?php" . "\r\n" . implode("\r\n", $headers) . "\r\n" . $encodeContent . "\r\n" . "?>";
        return $this;
    }

    /**
     * 获取解密后内容
     * @param $sourceFileContent 解密前内容
     * @return $this
     */
    private function decodeTemplate($sourceFileContent)
    {
        //以eval为标志 截取为数组，前半部分为密文中的替换掉的函数名，后半部分为密文
        $m = explode('eval', $sourceFileContent);
        //对系统函数的替换部分进行执行，得到系统变量
        $varStr = substr($m[0], strpos($m[0], '$'));
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
        $this->_writeContent = ${$this->q1}(
            ${$this->q3}(
                ${$this->q4}(
                    ${$this->q2},
                    ${$this->q5}*2
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
        if (is_dir($this->_sourceFile)) {
            $total=0;
            $copy=0;
            $encode=0;
            // \var_dump($this->_sourceFileArray);
            foreach ($this->_sourceFileArray as $k => $v) {
                $target = $this->_targetFile.str_replace($this->_sourceFile, '', $v);
                // \var_dump($target, $this->_targetFile, $this->_sourceFile, $v);
                $ext=$this->files->getExt($v);
                if ($ext!='php') {
                    copy($v, $target);
                    echo \sprintf("复制文件：{$v}\n{$target}\n-----------------\n");
                    $copy++;
                } else {
                    $this->encodeText($v)->encodeTemplate()->write($target);
                    echo \sprintf("加密前文件：{$v}\n加密后文件：{$target}\n-----------------\n");
                    $encode++;
                }
                $total++;
            }
            $rt=\sprintf("{$this->_sourceFile}:加密{$encode}个文件，复制{$copy}个文件，共处理{$total}个文件\n\n******************************************************************\n\n");
            echo $rt;
            return $rt;
        }
        // 加密单个文件
        else {
            $ext=$this->files->getExt($this->_sourceFile);
            if ($ext!='php') {
                echo '不支持这种文件格式';
                return;
            }
            // $this->files->delFile($this->_targetFile);
            $this->encodeText($this->_sourceFile)->encodeTemplate()->write($this->_targetFile);
            $rt=\sprintf("加密前文件：{$this->_sourceFile}\n加密后文件：{$this->_targetFile}\n-----------------\n");
            echo $rt;
            return $rt;
        }
    }

    /**
     * 解密函数
     */
    public function decode()
    {
        if (is_dir($this->_sourceFile)) {
            $total=0;
            $copy=0;
            $encode=0;
            foreach ($this->_sourceFileArray as $k => $v) {
                $target = $this->_targetFile.str_replace($this->_sourceFile, '', $v);
                $ext=$this->files->getExt($v);
                if ($ext!='php') {
                    copy($v, $target);
                    echo \sprintf("复制文件：{$v}\n{$target}\n-----------------\n");
                    $copy++;
                } else {
                    $sourceFileContent = file_get_contents($v);
                    $this->decodeTemplate($sourceFileContent)->write($target);
                    echo \sprintf("解密前文件：{$v}\n解密后文件：{$target}\n-----------------\n");
                    $encode++;
                }
                $total++;
            }
            $rt=\sprintf("{$this->_sourceFile}:解密{$encode}个文件，复制{$copy}个文件，共处理{$total}个文件\n\n******************************************************************\n\n");
            echo $rt;
            return $rt;
        } else {
            $ext=$this->files->getExt($this->_sourceFile);
            if ($ext!='php') {
                echo '不支持这种文件格式';
                return;
            }
            $sourceFileContent = file_get_contents($this->_sourceFile);
            $this->decodeTemplate($sourceFileContent)->write($this->_targetFile);
            $rt=\sprintf("解密前文件：{$this->_sourceFile}\n解密后文件：{$this->_targetFile}\n-----------------\n");
            echo $rt;
            return $rt;
        }
    }
}
