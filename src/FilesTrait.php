<?php

namespace Dfer\Tools;

/**
 * +----------------------------------------------------------------------
 * | 文件处理
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
trait FilesTrait
{

    /**
     * 读取文件的所有字符串
     * @param {Object} $fileN    物理路径
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
     * @param {Object} $dir    目录的物理路径
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
     * @param {Object} $strSrcDir    原始目录
     * @param {Object} $strDstDir    目标目录
     */
    public function copyDir($strSrcDir, $strDstDir, $quiet = false)
    {
        $dir = opendir($strSrcDir);
        if (!$dir) {
            return false;
        }
        if (!is_dir($strDstDir)) {
            $this->mkDirs($strDstDir);
        }
        while (false !== ($file = readdir($dir))) {
            if (!$quiet)
                echo $file . "\n";
            if (($file != '.') && ($file != '..')) {
                if (is_dir($strSrcDir . DIRECTORY_SEPARATOR . $file)) {
                    if (!$this->copyDir($strSrcDir . DIRECTORY_SEPARATOR . $file, $strDstDir . DIRECTORY_SEPARATOR . $file, $quiet)) {
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
     * @param {Object} $dir    文件夹路径
     */
    public function deleteDir($dir, $quiet = false)
    {
        if (is_dir($dir)) {
            if ($dp = opendir($dir)) {
                while (($file = readdir($dp)) != false) {
                    if ($file != '.' && $file != '..') {
                        $file = $dir . DIRECTORY_SEPARATOR . $file;
                        if (is_dir($file)) {
                            if (!$quiet)
                                echo "deleting dir:" . $file . "\n";
                            $this->deleteDir($file, $quiet);
                        } else {
                            try {
                                if (!$quiet)
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
                if (!$quiet)
                    echo 'Not permission' . "\n";
            }
        }
    }

    /**
     * 通用复制
     * @param {Object} $strSrc    原始路径
     * @param {Object} $strDst    目标路径
     **/
    public function copy($strSrc, $strDst, $quiet = false)
    {
        if (is_dir($strSrc)) {
            return $this->copyDir($strSrc, $strDst, $quiet);
        } else {
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
     * @param {Object} $file_name   文件名
     */
    public function getExt($file_name)
    {
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (!$ext) {
            $file_name = $this->removeQueryParams($file_name);
            //用.号对字符串进行分组
            $file_name_arr = explode('.', $file_name);
            //获取数组最后一条数据
            return array_pop($file_name_arr);
        }
        return $ext;
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
     * @param {Object} $file_src    文件路径
     * @param {Object} $type    写入类型 a 追加 w覆盖
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
     * @param {Object} $edit_tool    上传组件类型
     * @param {Object} $option    特殊配置
     * @param {Object} $upload_root    上传目录
     */
    public function uploadFile($edit_tool = self::UPLOAD_UMEDITOR_SINGLE, $option = null, $upload_root = 'upload')
    {
        // var_dump($edit_tool);die;

        // 上传组件的name
        $name = $option['name'] ?? 'file';
        // 自定义路径
        $path = $option['path'] ?? '';
        //开启图片压缩
        $compress = $option['compress'] ?? false;
        // 自定义图片尺寸
        $size = $option['size'] ?? '';

        $fileSizeMax = FILE_SIZE_MAX;

        if ($_FILES == null) {
            $this->showJson(false, null, null, '没有找到文件');
        }

        $file = $_FILES;
        $filename = $_FILES[$name]["name"];
        $filetype = $_FILES[$name]["type"];
        $filesize = $_FILES[$name]["size"] / 1024;
        $filetmpname = $_FILES[$name]["tmp_name"];
        $fileErr = $_FILES[$name]["error"];

        //以byte为单位
        if ($filesize > $fileSizeMax) {
            $msg = '文件超出尺寸';
            $this->uploadFileJson(false, compact('msg'), $edit_tool);
        }
        if ($fileErr > 0) {
            $msg = '文件上传受限';
            $this->uploadFileJson(false, compact('msg'), $edit_tool);
        }


        switch ($filetype) {
            //图片上传
            case 'image/gif':
            case 'image/jpeg':
            case 'image/pjpeg':
            case 'image/png':
            case 'image/vnd.microsoft.icon':
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
                    $size = $this->split($size, "*");
                    $this->resizeJpg($filetmpname, $new_name, $size[0], $size[1]);  #将临时文件转变尺寸之后移动到网站目录
                } else {
                    #将临时文件移动到网站目录
                    move_uploaded_file($filetmpname, $new_name);
                }

                if ($compress) {
                    #原图压缩，不缩放，但体积大大降低
                    $percent = 1;
                    $imgcompress = new ImgCompress($new_name, $percent);
                    $image = $imgcompress->compressImg($new_name);
                }
                $type = 'img';
                $new_name = '/' . $new_name;
                $this->uploadFileJson(true, compact('type', 'new_name', 'file'), $edit_tool);
                break;
            case 'audio/mp3':
            case 'audio/mpeg':
                // 音乐上传
                $path = str("{0}/music/{1}/", [$upload_root, date("Ymd")]);
                break;
            case 'application/zip':
                // zip文件上传
                $path = str("{0}/zip/{1}/", [$upload_root, date("Ymd")]);
                break;
            case 'video/mp4':
                // video文件上传
                $path = str("{0}/video/{1}/", [$upload_root, date("Ymd")]);
                break;
            default:
                $msg = "不支持的文件类型:{$filetype}";
                $this->uploadFileJson(false, compact('msg', 'file'), $edit_tool);
                break;
        }

        $this->mkDirs($path);
        //新文件名
        $new_name = sprintf("%s/%s-%s.%s", $path, rand(10000, 99999), date("Ymdhis"), $this->getExt($filename));

        #将临时文件移动到网站目录
        move_uploaded_file($filetmpname, $new_name);

        $type = 'file';
        $new_name = '/' . $new_name;
        $this->uploadFileJson(true, compact('type', 'new_name'), $edit_tool);
        return false;
    }

    /**
     * 不同组件的返回格式
     * @param {Object} $var 变量
     **/
    public function uploadFileJson($status, $data, $edit_tool = self::UPLOAD_UMEDITOR_EDITOR)
    {
        extract($data);
        $msg = $msg ?? null;

        //js上传插件会接收所有的echo数据
        switch ($edit_tool) {
            case self::UPLOAD_UMEDITOR_EDITOR:
                $stateMap = array(    //上传状态映射表，国际化用户需考虑此处数据的国际化
                    "SUCCESS",                //上传成功标记，在UEditor中内不可改变，否则flash判断会出错
                );
                $return = array(
                    "originalName" => null,
                    "name" => null,
                    "url" => $new_name,
                    "size" => null,
                    "type" => $type,
                    "state" => $status ? $stateMap[0] : $msg
                );
                $this->showJsonBase($return, false);
                break;
            case self::UPLOAD_WEB_UPLOADER:
                $json = ["type" => $type, "url" => $new_name];
                $this->showJson($status, $json, $status ? $msg : null, !$status ? $msg : null);
                break;
            case self::UPLOAD_UMEDITOR_SINGLE:
                $this->showJsonBase($this->delSpace($new_name));
                break;
            case self::UPLOAD_LAYUI_EDITOR:
                $json = array('code' => 0, 'msg' => $msg, 'data' => array('src' => $new_name, 'title' => $new_name));
                $this->showJsonBase($json);
                break;
            case self::UPLOAD_EDITORMD_EDITOR:
                // http://editor.md.ipandao.com/examples/image-upload.html
                $json = ["success" => $status ? 1 : 0, "url" => $new_name, "message" => $msg, "debug" => $data];
                $this->showJsonBase($json);
                break;
            default:
                $this->showJson($status, $data, $status ? $msg : null, !$status ? $$msg : null);
                break;
        }
    }

    /**
     * 获取目录中最新修改的文件名
     * @param {Object} $directory 你要读取的目录路径
     **/
    public function getNewestFileName($directory = null)
    {
        $latestFile = '';
        $latestTime = 0;

        foreach (scandir($directory) as $file) {
            if (in_array($file, ['.', '..'])) {
                continue; // 跳过当前目录和上级目录
            }
            $filePath = $directory . '/' . $file;
            $mtime = filemtime($filePath);
            if ($mtime > $latestTime) {
                $latestTime = $mtime;
                $latestFile = $file;
            }
        }
        return $latestFile;
    }

    /**
     * 输出调试信息到日志文件
     * @param {Object} 自动获取所有参数
     **/
    public function debug()
    {
        $args = $this->str(func_get_args());
        $time = $this->getTime(time());
        // 项目根目录
        $root = $this->getRootPath();
        $tag = $_SERVER['REQUEST_URI'] ?? '';
        $trace = $this->filterBacktrace();
        // var_dump($trace);
        $str = $this->str(
            <<<STR

            ********************** DEBUG{tag} START **********************
            {$trace}

            {0}
            **********************  DEBUG{tag} END  **********************

            STR
            ,
            [$args, 'tag' => "[{$tag} {$time}]"]
        );
        $file_dir = $this->str("{root}/data/logs/{0}", [date('Ym'), "root" => $root]);
        $this->mkDirs($file_dir);
        $file_src = $this->str("{0}/{1}.log", [$file_dir, date('d')]);
        $this->writeFile($str, $file_src, "a");
    }

    /**
     * 过滤debug_backtrace
     * @return {Array} 存在file参数的堆栈数组
     **/
    public function filterBacktrace($var = null)
    {
        // 当前代码的运行堆栈跟踪信息
        $trace = debug_backtrace();
        $filteredBacktrace = [];
        foreach ($trace as $index => $call) {
            // 检查是否有 'file' 键
            if (isset($call['file'])) {
                if ($this->findStr($call['file'], "/vendor/")) {
                    continue;
                }
                $filteredBacktrace[] = $this->str("{file}:{line}", ['file' => $call['file'], 'line' => $call['line']]);
                if (count($filteredBacktrace) == 9) {
                    break;
                }
            }
        }
        $filteredBacktraceString = implode(PHP_EOL, $filteredBacktrace);
        return $filteredBacktraceString;
    }

    /**
     * 获取项目根目录
     * @param {Object} $var 变量
     **/
    public function getRootPath($var = null)
    {
        $root = dirname(__DIR__, 4);
        return $root;
    }

    /**
     * 获取网络文件
     *
     * 例：
     * Common::getFileFromUrl("https://fyb-1.cdn.bcebos.com/fyb/de6163834f53ca92c1273fff98ac9078.jpeg?x-bce-process=image/resize,m_fill,w_256,h_170")
     *
     * @param {Object} $url    文件远程地址。如：https://fyb-1.cdn.bcebos.com/fyb/de6163834f53ca92c1273fff98ac9078.jpeg?x-bce-process=image/resize,m_fill,w_256,h_170
     * @param {Object} $dir    本地保存目录。如：/www/wwwroot/www.dfer.site/public/uploads/collect
     * @return {Object}
     * $dir为空：返回base64字符串
     * $dir不为空：返回文件保存路径
     */
    public function getFileFromUrl($url, $dir = null)
    {
        // $url 以 // 开头，就在其前面加上 https: 协议
        if (strncmp($url, '//', 2) === 0) {
            $url = 'https:' . $url;
        }
        // 读取文件的内容
        $file_data = file_get_contents($url);
        // 获取文件类型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $file_data);
        finfo_close($finfo);
        list($type, $ext) = explode('/', $mimeType);

        if ($dir) {
            Common::mkDirs($dir);
            // 把url转化为base64字符串作为文件名
            $fileName = base64_encode($url) . '.' . $ext;
            $src = Common::str("{0}/{1}", [$dir, $fileName]);
            file_put_contents($src, $file_data);
            return $src;
        } else {
            $file_base64 = "data:{$mimeType};base64," . base64_encode($file_data);
            return $file_base64;
        }
    }
}
