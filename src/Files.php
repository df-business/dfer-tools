<?php

namespace Dfer\Tools;

/**
 * +----------------------------------------------------------------------
 * | 操作文件
 * +----------------------------------------------------------------------
 *                      .::::.
 *                    .::::::::.            | AUTHOR: dfer
 *                    :::::::::::           | EMAIL: df_business@qq.com
 *                 ..:::::::::::'           | QQ: 3504725309
 *             '::::::::::::'
 *                .::::::::::
 *           '::::::::::::::..
 *                ..::::::::::::.
 *              ``::::::::::::::::
 *               ::::``:::::::::'        .:::.
 *              ::::'   ':::::'       .::::::::.
 *            .::::'      ::::     .:::::::'::::.
 *           .:::'       :::::  .:::::::::' ':::::.
 *          .::'        :::::.:::::::::'      ':::::.
 *         .::'         ::::::::::::::'         ``::::.
 *     ...:::           ::::::::::::'              ``::.
 *   ```` ':.          ':::::::::'                  ::::..
 *                      '.:::::'                    ':'````..
 * +----------------------------------------------------------------------
 *
 */
class Files
{
    protected $common = null;
    protected $img_common = null;
    public function __construct()
    {
        $this -> common = new \Dfer\Tools\Common;
        $this -> img_common = new \Dfer\Tools\Img\Common;
    }
    
    /**
     * 读取文件的所有字符串
     *
     * 物理路径
     */
    public function readFileStr($fileN)
    {
        $file_path = $fileN;
        if (file_exists($file_path)) {
            $str = file_get_contents($file_path);
            //将整个文件内容读入到一个字符串中
            $str = str_replace("\n", "<br />", $str);
            return $str;
        } else {
            return 'file not exist';
        }
    }
    
    /**
     *
     * 下载文件，隐藏真实下载地址
     * 下载路径显示的是下载页面的url
     * 处在同步调用下，方能生效
     *
     */
    public function downloadDocument($fileSrc, $mimetype="application/octet-stream")
    {
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Disposition: attachment; filename = $filename");
        header("Content-Length: " . filesize($fileSrc));
        header("Content-Type: $mimetype");
        echo file_get_contents($fileSrc);
    }
    
    
    /**
     * 遍历目录，获取目录、文件数组
     */
    public $my_scenfiles = array();
    public $my_files = array();
    public function scanDir($dir)
    {
        global $my_scenfiles, $my_files;
        //  echo $dir;
        if ($handle = opendir($dir)) {
            while (($file = readdir($handle)) !== false) {
                if ($file != ".." && $file != ".") {
                    if (is_dir($dir . "/" . $file)) {
                        $this->scanDir($dir . "/" . $file);
                    } else {
                        $my_scenfiles[] = $dir . "/" . $file;
                        $my_files[] = $file;
                    }
                }
            }
            closedir($handle);
        }
    }
    
    
    /**
     *
     * 删除目录和目录下的文件，成功则返回1
     * $dir	目录的物理路径
     *
     * @return mixed
     **/
    public function delDir($dir)
    {
        try {
            //先删除目录下的文件：
            $dh = opendir($dir);
            while ($file = readdir($dh)) {
                if ($file != "." && $file != "..") {
                    $fullpath = $dir . "/" . $file;
                    if (!is_dir($fullpath)) {
                        $rt = unlink($fullpath);
                        if (!$rt) {
                            return false;
                        }
                    } else {
                        $this -> deldir($fullpath);
                        //循环删除文件
                    }
                }
            }
            closedir($dh);
            //删除当前文件夹
            return rmdir($dir);
        } catch (\Exception $e) {
            return 0;
        } catch (\Throwable  $e) {
            return 0;
        }
    }
                
    /**
     * 删除单个文件
     * @param {Object} $file
     */
    public function delFile($file)
    {
        return unlink($file);
    }
                
    /**
     * 遍历一个目录下的所有文件和文件夹，返回一个easyUI的json字符串
     * @param {Object} $dir
     */
    public function getNextTree($dir)
    {
        $fileArr = '';
        $dirArr = '';
        $list = scandir($dir);
        foreach ($list as $key => $val) {
            if ($val == '..' || $val == '.') {
                continue;
            }
            if (is_file($dir . $val)) {
                $fileArr = $fileArr . '{"text":"' . $val . '","src":"' . $dir . $val . '"},';
            } else {
                $files = $this -> getNextTree($dir . $val . '/');
                $dirArr = $dirArr . '{"text":"' . $val . '","src":"' . $dir . $val . '","children":[' . $files . '],"state":"closed"},';
            }
        }
        $fileArr = $dirArr . $fileArr;
        $fileArr = substr($fileArr, 0, strlen($fileArr) - 1);
        return $fileArr;
    }
                
     
    /**
     * 获取文件后缀
     * @param {Object} $file_name
     */
    public function getExt($file_name)
    {
        $a = explode('.', $file_name);
        return array_pop($a);
        //获取数组最后一条数据
                        //用.号对字符串进行分组
    }
    
    
    /**
     * 获取文件后缀
     * @param   string  $name   文件名
     * @return  string
     */
    public function getFileExt($name)
    {
        return strtolower(pathinfo($name, PATHINFO_EXTENSION));
    }
    
    /**
     * 判断文件是否存在
     * @param {Object} $filename
     */
    public function fileExist($filename)
    {
        if (file_exists($filename)) {#查看文件是否存在于网站目录
            return true;
        } else {
            return false;
        }
    }
    
    
    /**
     * 创建一个文件，写入字符串，存在则覆盖
     * @param {Object} $fileN
     * @param {Object} $str
     */
    public function fileW($fileN, $str)
    {
        $fp = fopen($fileN, "w");
        fwrite($fp, $str);
        fclose($fp);
    }
    
    /**
     *
     * 拼接js或者css
     * eg：/index.php?f=/css_js/df.js,/css_js/FontFamily/init.js
     */
    public function addF($f)
    {
        $files = explode(",", $f);
    
        foreach ($files as $v) {
            $v=ROOT.$v;
    
            //echo $v;
            $myfile = fopen($v, "r") or die("Unable to open file!");
            $str=fread($myfile, filesize($v));
            fclose($myfile);
            $rt.=$str;
        }
    
        die(empty($rt)?'':$rt);
    }
    
    /**
     *
     * 上传文件
     * 配合上传组件使用
     * js上传组件会接收所有的echo
     * 默认不改尺寸，不使用富文本框的上传组件
     * 建议每个视图都单独使用一个控制器下的方法来调用这个函数
     *
     * 支持图片、音乐
     * 目前不支持icon
     *
     *
     * 可以拓展出任何文件的上传
     *
     *
     * 可以用来覆盖特定文件
     * upload_file($name,"120*120",0,'img/ewm1.jpg');
     *
     *
     * upload_file($name,0,2);  //layui编辑器上传
     *
     * 返回文件的上传路径
     *
     *
     * $name：控件的name参数
     */
    public function uploadFile($name, $size=0, $editTool=0, $Path='')
    {
        global $common,$img_common;
                                
        //区分不同的数据类型
        switch ($name) {
        case 'vtour':break;
        case 'upfile':break;
        default:break;
    }
    
        //设置文件上传的最大尺寸
    $imgSize=104857600;//100M    1024*1024*100
    $mp3Size=104857600;
        $zipSize=104857600;
        //die(json_encode($_FILES));
    
        if ($_FILES==null) {
            die("not file");
        }
    
        $filename=$_FILES[$name]["name"];
        $filetype=$_FILES[$name]["type"];
        $filesize=$_FILES[$name]["size"] / 1024;
        $filetmpname=$_FILES[$name]["tmp_name"] ;
        $fileErr=$_FILES[$name]["error"];
        //图片上传
    if (($filetype == "image/gif")        #对应input——name=“file”     #判断文件类型和大小
    || ($filetype == "image/jpeg")
    || ($filetype == "image/pjpeg")
    || ($filetype == "image/png")
    //|| ($filetype == "image/vnd.microsoft.icon")
    ) {
        //	die();
        //以byte为单位
        if ($filesize > $imgSize) {
            echo "-3";
        } //超出尺寸
        else {
            if ($fileErr > 0) {       #判断是否出错
        echo "-1";//文件出错
            } else {
                $path="upload/pics/".$name.'/';
                mkdirs($path);
                //自动生成路径
                if (empty($Path)) {
                    $newname=$path.$name."_".date("Ymdhis").".".$this->get_ext($filename);
                }  //新文件名
                //固定路径
                else {
                    $newname=$Path;
                }  //新文件名
    
                if ($size) {
                    $size=$common->split($size, "*");
                    $img_common->resizeJpg($filetmpname, $newname, $size[0], $size[1]);  #将临时文件转变尺寸之后移动到网站目录
                } else {
                    move_uploaded_file($filetmpname, $newname);
                }         #将临时文件移动到网站目录
    
                $ys=true;//开启图片压缩
                if ($ys) {
                    $percent = 1;  #原图压缩，不缩放，但体积大大降低
                    $imgcompress=new \Dfer\Tools\Img\Compress($newname, $percent);
                    $image = $imgcompress->compressImg($newname);
                }
    
    
                $newname='/'.$newname;
                //header("Content-type: image/jpeg");
                //js上传插件会接收所有的echo数据
                //um单个文件上传
                if ($editTool==0) {
                    echo $common->delSpace($newname);
                }//js上传插件会接收所有的echo数据
                //um编辑框
                elseif ($editTool==1) {
                    echo "{'url':'{$newname}','state':'SUCCESS',name:'',originalName:'',size:'',type:''}";
                }
                //layui编辑器上传
                elseif ($editTool==2) {
                    $json=array(code=>0,msg=>'error','data'=>array('src'=>$newname,'title'=>$newname));
                    echo(json_encode($json));
                }
            }
        }
    }
    
        //其他文件
        else {
            //音乐上传
            if (($filetype == "audio/mp3")
    ||($m->get_ext($filename)=="mp3")
    ) {
                $path="upload/music/";
                $fileSizeMax=$mp3Size;
            }
            //zip文件上传
            elseif (($filetype == "application/zip")
    ||($m->get_ext($filename)=="zip")
    ) {
                $path="upload/zip/";
                $fileSizeMax=$zipSize;
            }
            //video文件上传
            elseif (($filetype == "video/mp4")
    ||($m->get_ext($filename)=="mp4")
    ) {
                $path="upload/video/";
                $fileSizeMax=$zipSize;
            } else {
                die("-2");                #不支持的文件类型
            }
    
            //以byte为单位
            if ($filesize> $fileSizeMax) {
                echo "-3";
            } //超出尺寸
            else {
                if ($fileErr > 0) {       #判断是否出错
        echo "-1";//上传受限
                } else {
                    mkdirs($path);
                    $newname=$path.$name."_".date("Ymdhis").".".$m->get_ext($filename);  //新文件名
    move_uploaded_file($filetmpname, $newname);         #将临时文件移动到网站目录
    //header("Content-type: image/jpeg");
    $newname='/'.$newname;
                    echo delSpace($newname);//js上传插件会接收所有的echo数据
                }
            }
        }
        return $newname;
    }
}
