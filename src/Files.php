<?php

namespace Dfer\Tools;

/**
 * +----------------------------------------------------------------------
 * | 操作文件
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
class Files
{
    protected $common = null;
    protected $img_common = null;
    public function __construct()
    {
        $this->common = new \Dfer\Tools\Common;
        $this->img_common = new \Dfer\Tools\Img\Common;
    }

    /**
     * 读取文件的所有字符串
     * @param {Object} $fileN	物理路径
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
    public function downloadDocument($fileSrc, $mimetype = "application/octet-stream")
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
     * 删除目录和目录下的文件，成功则返回1
     * @param {Object} $dir	目录的物理路径
     */
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
                        $this->deldir($fullpath);
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


    /*
	 * 创建目录
	 *
	 * 如果目录不存在就根据路径创建无限级目录
	 *
	 *
	 */
    public function mkDirs($path)
    {
        //检查指定的文件是否是目录
        if (!is_dir($path)) {
            //循环创建上级目录
            $this->mkDirs(dirname($path));
            mkdir($path);
        }
        return is_dir($path);
    }

	/**
	 * 覆盖文件夹的内容
	 * @param {Object} $strSrcDir	原始目录
	 * @param {Object} $strDstDir	目标目录
	 */
	public function copyDir($strSrcDir, $strDstDir,$quiet=false)
	{
	    $dir = opendir($strSrcDir);
	    if (!$dir) {
	        return false;
	    }
	    if (!is_dir($strDstDir)) {
									$this->mkDirs($strDstDir);
	    }
	    while (false !== ($file = readdir($dir))) {
									if(!$quiet)
	        echo $file . "\n";
	        if (($file != '.') && ($file != '..')) {
	            if (is_dir($strSrcDir . DIRECTORY_SEPARATOR . $file)) {
	                if (!$this->copyDir($strSrcDir . DIRECTORY_SEPARATOR . $file, $strDstDir . DIRECTORY_SEPARATOR . $file,$quiet)) {
	                    return false;
	                }
	            } else {
	                if (!copy($strSrcDir . DIRECTORY_SEPARATOR . $file, $strDstDir . DIRECTORY_SEPARATOR . $file)) {
	                    return false;
	                }
	            }
	        }
	    }
	    closedir($dir);
	    return true;
	}

	/**
	 * 清除文件夹
	 * @param {Object} $dir	文件夹路径
	 */
	public function deleteDir($dir,$quiet=false)
	{
	    if (is_dir($dir)) {
	        if ($dp = opendir($dir)) {
	            while (($file = readdir($dp)) != false) {
	                if ($file != '.' && $file != '..') {
	                    $file = $dir . DIRECTORY_SEPARATOR . $file;
	                    if (is_dir($file)) {
																						if(!$quiet)
	                        echo "deleting dir:" . $file . "\n";
	                        $this->deleteDir($file,$quiet);
	                    } else {
	                        try {
																										if(!$quiet)
	                            echo "deleting file:" . $file . "\n";
	                            unlink($file);
	                        } catch (\Exception $e) {

	                        }
	                    }
	                }
	            }
	            if (readdir($dp) == false) {
	                closedir($dp);
	                rmdir($dir);
	            }
	        } else {
										if(!$quiet)
	            echo 'Not permission' . "\n";
	        }

	    }
	}

	/**
	 * 通用复制
	 * @param {Object} $strSrc	原始路径
	 * @param {Object} $strDst	目标路径
	 **/
	public function copy($strSrc, $strDst,$quiet=false)
	{
		if (is_dir($strSrc)) {
			return $this->copyDir($strSrc, $strDst,$quiet);
		}
		else{
			if (!copy($strSrc, $strDst)) {
			    return false;
			}
		}
		return true;
	}


    /**
     * 遍历一个目录下的所有文件和文件夹，返回一个字符串
     * @param {Object} $dir
     * @return {String} easyUI的json字符串
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
                $files = $this->getNextTree($dir . $val . '/');
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
        //获取数组最后一条数据
        //用.号对字符串进行分组
        $a = explode('.', $file_name);
        return array_pop($a);
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
        #查看文件是否存在于网站目录
        if (file_exists($filename)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 创建一个文件，写入字符串，存在则覆盖
     * 自动根据路径创建上级文件夹
     * @param {Object} $str
     * @param {Object} $file_src	文件路径
     * @param {Object} $type	写入类型 a 追加 w覆盖
     */
    public function writeFile($str, $file_src, $type = "w")
    {
        $this->mkDirs(dirname($file_src));
        $fp = fopen($file_src, $type) or die("无法打开文件!");
        fwrite($fp, $str);
        fclose($fp);
    }

    /**
     *
     * 拼接js或者css
     * eg：/index.php?f=/css_js/df.js,/css_js/FontFamily/init.js
     */
    public function addFile($f)
    {
        $files = explode(",", $f);

        foreach ($files as $v) {
            $v = ROOT . $v;

            //echo $v;
            $myfile = fopen($v, "r") or die("Unable to open file!");
            $str = fread($myfile, filesize($v));
            fclose($myfile);
            $rt .= $str;
        }

        die(empty($rt) ? '' : $rt);
    }

    //um单个文件上传
    const UPLOAD_UMEDITOR_SINGLE = 0;
    //um编辑框
    const UPLOAD_UMEDITOR_EDITOR = 1;
    //layui编辑器上传
    const UPLOAD_LAYUI_EDITOR = 2;
    //editormd编辑器上传
    const UPLOAD_EDITORMD_EDITOR = 3;
    //baidu组件上传
    const UPLOAD_WEB_UPLOADER = 4;

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
     * $files->uploadFile($name,"120*120",0,'img/ewm1.jpg');
     *
     *
     * $files->uploadFile($name,0,2);  //layui编辑器上传
     *
     * 返回文件的上传路径
     *
     *
     * @param {Object} $edit_tool	上传组件类型
     * @param {Object} $option	特殊配置
     * @param {Object} $upload_root	上传目录
     */
    public function uploadFile($edit_tool = self::UPLOAD_UMEDITOR_SINGLE, $option = null, $upload_root = 'upload')
    {
        global $common;
        // var_dump($edit_tool);die;

        // 上传组件的name
        $name = $option['name'] ?? 'file';
        // 自定义路径
        $path = $option['path'] ?? '';
        //开启图片压缩
        $compress = $option['compress'] ?? false;
        // 自定义图片尺寸
        $size = $option['size'] ?? '';

        $img_common = new \Dfer\Tools\Img\Common;

        $fileSizeMax = FILE_SIZE_MAX;
        //die(json_encode($_FILES));

        if ($_FILES == null) {
            return "not file";
        }

        $filename = $_FILES[$name]["name"];
        $filetype = $_FILES[$name]["type"];
        $filesize = $_FILES[$name]["size"] / 1024;
        $filetmpname = $_FILES[$name]["tmp_name"];
        $fileErr = $_FILES[$name]["error"];

        //以byte为单位
        if ($filesize > $fileSizeMax) {
            //超出尺寸
            return "-3";
        }
        if ($fileErr > 0) {
            //上传受限
            return "-1";
        }

        //图片上传
        if (in_array($filetype, ['image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/vnd.microsoft.icon'])) {
            //自动生成路径
            if ($path) {
                $new_name = $path;
            } else {
                $path = str("{0}/img/{1}/", [$upload_root, date("Ymd")]);
                $this->mkDirs($path);
                //新文件名
                $new_name = str("{0}/{1}.{2}", [$path, base64_encode(json_encode([$filename, $filesize])), $this->getExt($filename)]);
            }

            if ($size) {
                $size = $common->split($size, "*");
                $img_common->resizeJpg($filetmpname, $new_name, $size[0], $size[1]);  #将临时文件转变尺寸之后移动到网站目录
            } else {
                #将临时文件移动到网站目录
                move_uploaded_file($filetmpname, $new_name);
            }

            if ($compress) {
                #原图压缩，不缩放，但体积大大降低
                $percent = 1;
                $imgcompress = new  \Dfer\Tools\Img\Compress($new_name, $percent);
                $image = $imgcompress->compressImg($new_name);
            }

            $new_name = '/' . $new_name;
            //header("Content-type: image/jpeg");
            //js上传插件会接收所有的echo数据
            switch ($edit_tool) {
                case self::UPLOAD_UMEDITOR_SINGLE:
                    return $common->delSpace($new_name);
                    break;
                case self::UPLOAD_UMEDITOR_EDITOR:
                    return "{'url':'{$new_name}','state':'SUCCESS',name:'',originalName:'',size:'',type:''}";
                    break;
                case self::UPLOAD_LAYUI_EDITOR:
                    $json = array(code => 0, msg => 'error', 'data' => array('src' => $new_name, 'title' => $new_name));
                    return $common->showJsonBase($json);
                    break;
                case self::UPLOAD_EDITORMD_EDITOR:
                    $json = ["success" => 1, "url" => $new_name, "state" => 'SUCCESS', "name" => "", "originalName" => '', "size" => '', "type" => ''];
                    return $common->showJsonBase($json);
                    break;
                case self::UPLOAD_WEB_UPLOADER:
                    $json = ["type" => 'img', "url" => $new_name];
                    return $common->showJsonBase($json);
                    break;
                default:
                    return $new_name;
                    break;
            }
        } else {
            //音乐上传
            if (in_array($filetype, ['audio/mp3']) || $m->getExt($filename) == "mp3") {
                $path = str("{0}/music/{1}/", [$upload_root, date("Ymd")]);
            }
            //zip文件上传
            elseif (in_array($filetype, ['application/zip']) || $m->getExt($filename) == "zip") {
                $path = str("{0}/zip/{1}/", [$upload_root, date("Ymd")]);
            }
            //video文件上传
            elseif (in_array($filetype, ['video/mp4']) || $m->getExt($filename) == "mp4") {
                $path = str("{0}/video/{1}/", [$upload_root, date("Ymd")]);
            } else {
                #不支持的文件类型
                return "-2";
            }

            $this->mkDirs($path);
            //新文件名
            $new_name = sprintf("%s/%s-%s.%s", $path, rand(10000, 99999), date("Ymdhis"), $this->getExt($filename));

            #将临时文件移动到网站目录
            move_uploaded_file($filetmpname, $new_name);
            //header("Content-type: image/jpeg");
            $new_name = '/' . $new_name;


            switch ($edit_tool) {
                case self::UPLOAD_WEB_UPLOADER:
                    $json = ["type" => 'file', "url" => $new_name];
                    return $common->showJsonBase($json);
                    break;
                default:
                    //js上传插件会接收所有的echo数据
                    return $common->delSpace($new_name);
                    break;
            }
        }
        return false;
    }


}
