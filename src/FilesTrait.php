<?php

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

namespace Dfer\Tools;

trait FilesTrait
{
    /**
     * 读取文件的所有字符串
     * @param {Object} $file_path    物理路径
     * @param {Object} $replace_eol    替换换行符
     */
    public function readFile($file_path, $replace_eol = false)
    {
        if (file_exists($file_path)) {
            $str = file_get_contents($file_path);
            if ($replace_eol)
                $str = str_replace(PHP_EOL, "<br />", $str);
            return $str;
        } else {
            return false;
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
        } catch (Exception $e) {
            return 0;
        } catch (Throwable  $e) {
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
            // 如果上级目录不存在，则尝试创建它们
            @mkdir($path, 0777, true);
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
                            } catch (Exception $e) {
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
        if (preg_match('/^https?:\/\//i', $file_name)) {
            // 是远程文件
            // 使用 @ 运算符来抑制可能的错误消息（例如，当 URL 无效时）
            $imageInfo = @getimagesize($file_name);
            if ($imageInfo !== false) {
                // 返回 MIME 类型
                return $this->getKeyByValue($this->getMimeType(), $imageInfo['mime']);
            }
        }

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
        $str = $str ?: '';
        $file_dir = dirname($file_src);
        $this->mkDirs($file_dir);
        @chmod($file_dir, 0777);
        $fp = fopen($file_src, $type);
        if ($fp === false) {
            return false;
        }
        $bytes_written = fwrite($fp, $str);
        fclose($fp);
        if ($bytes_written === false || $bytes_written < strlen($str)) {
            return false;
        }
        return true;
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
    public function uploadFile($edit_tool = Constants::UPLOAD_UMEDITOR_SINGLE, $option = null, $upload_root = 'upload')
    {
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
            $this->uploadFileJson(Constants::FILE_NOT_FOUND, null, $edit_tool);
        }

        $file = $_FILES;
        $filename = $_FILES[$name]["name"];
        $filetype = $_FILES[$name]["type"];
        $filesize = $_FILES[$name]["size"] / 1024;
        $filetmpname = $_FILES[$name]["tmp_name"];
        $fileErr = $_FILES[$name]["error"];

        //以byte为单位
        if ($filesize > $fileSizeMax) {
            $this->uploadFileJson(Constants::FILE_SIZE_LIMIT, null, $edit_tool);
        }
        if ($fileErr > 0) {
            $this->uploadFileJson(Constants::FILE_UPLOAD_RESTRICTED, null, $edit_tool);
        }

        $mime_type = $this->getMimeTypePrefix($filetype);
        switch ($mime_type) {
            case 'image':
                if ($path) {
                    $new_name = $path;
                } else {
                    $path = $upload_root . DIRECTORY_SEPARATOR . $mime_type . DIRECTORY_SEPARATOR . $this->getTime(null, "Y") . DIRECTORY_SEPARATOR . $this->getTime(null, "m");
                    $this->mkDirs($path);
                    //新文件名
                    $new_name = $this->str("{0}/{1}.{2}", [$path, $this->generateShortUUID() . '.' . date("dHis"), $this->getExt($filename)]);
                }

                if ($size) {
                    $size = $this->split($size, "*");
                    // 将临时文件转变尺寸之后移动到网站目录
                    $this->resizeJpg($filetmpname, $new_name, $size[0], $size[1]);
                } else {
                    // 将临时文件移动到网站目录
                    move_uploaded_file($filetmpname, $new_name);
                }

                if ($compress) {
                    // 原图压缩，不缩放，但体积大大降低
                    $percent = 1;
                    $imgcompress = new ImgCompress($new_name, $percent);
                    $image = $imgcompress->compressImg($new_name);
                }
                $new_name = DIRECTORY_SEPARATOR . $new_name;
                $this->uploadFileJson(Constants::FILE_UPLOAD_SUCCESS, compact('mime_type', 'new_name', 'file'), $edit_tool);
                break;
            case 'audio':
            case 'video':
            case 'application':
                $path = $upload_root . DIRECTORY_SEPARATOR . $mime_type . DIRECTORY_SEPARATOR . $this->getTime(null, "Y") . DIRECTORY_SEPARATOR . $this->getTime(null, "m");
                $this->mkDirs($path);
                // 新文件名
                $new_name = $this->str("{0}/{1}.{2}", [$path, $this->generateShortUUID() . '.' . date("dHis"), $this->getExt($filename)]);
                // 将临时文件移动到网站目录
                move_uploaded_file($filetmpname, $new_name);
                $new_name = DIRECTORY_SEPARATOR . $new_name;
                $this->uploadFileJson(Constants::FILE_UPLOAD_SUCCESS, compact('mime_type', 'new_name'), $edit_tool);
                break;
            default:
                $this->uploadFileJson(Constants::FILE_TYPES_UNSUPPORTED, compact('filetype', 'file'), $edit_tool);
                break;
        }
    }

    /**
     * 不同组件的返回格式
     * @param {Object} $status_code 状态码
     * @param {Object} $data 补充数据
     * @param {Object} $edit_tool 组件类型
     **/
    public function uploadFileJson($status_code, $data = null, $edit_tool = Constants::UPLOAD_UMEDITOR_EDITOR)
    {
        $data = $data ?: ['new_name' => null, 'filetype' => null];
        extract($data);
        $msg = $this->getStatusMsg($status_code, $data);

        //js上传插件会接收所有的echo数据
        switch ($edit_tool) {
            case Constants::UPLOAD_UMEDITOR_EDITOR:
                //上传状态映射表，国际化用户需考虑此处数据的国际化
                $stateMap = array(
                    "SUCCESS"
                );
                $return = array(
                    "originalName" => null,
                    "name" => null,
                    "url" => $new_name,
                    "size" => null,
                    "type" => $mime_type,
                    "state" => $status_code === 0 ? $stateMap[0] : $msg
                );
                $this->showJsonBase($return, false);
                break;
            case Constants::UPLOAD_WEB_UPLOADER:
                $json = ["type" => $mime_type, "url" => $new_name];
                $this->showJson($status_code, $json, $status_code === 0 ? $msg : null, $status_code !== 0 ? $msg : null);
                break;
            case Constants::UPLOAD_UMEDITOR_SINGLE:
                $this->showJsonBase($this->delSpace($new_name));
                break;
            case Constants::UPLOAD_LAYUI_EDITOR:
                $json = array('code' => $status_code, 'msg' => $msg, 'data' => array('src' => $new_name, 'title' => $new_name));
                $this->showJsonBase($json);
                break;
            case Constants::UPLOAD_EDITORMD_EDITOR:
                // http://editor.md.ipandao.com/examples/image-upload.html
                $json = ["success" => $status_code === 0 ? 1 : 0, "url" => $new_name, "message" => $msg, "debug" => $data];
                $this->showJsonBase($json);
                break;
            default:
                $this->showJson($status_code, $data, $status_code ? $msg : null, !$status_code ? $$msg : null);
                break;
        }
    }

    /**
     * 获取状态信息
     * @param object $var 变量
     * @return mixed
     **/
    public function getStatusMsg($key = null, $param = null)
    {
        $list = [
            Constants::FILE_UPLOAD_SUCCESS => '文件上传成功',
            Constants::FILE_SIZE_LIMIT => '文件超出尺寸',
            Constants::FILE_UPLOAD_RESTRICTED => '文件上传受限',
            Constants::FILE_TYPES_UNSUPPORTED => '不支持的文件类型:{filetype}',
            Constants::FILE_NOT_FOUND => '没有找到文件',
            Constants::UNKOWN_ERROR => '未知错误',
        ];

        if ($key !== null) {
            // var_dump($list[$key]??$list[Constants::UNKOWN_ERROR],$param);
            return $this->str($list[$key] ?? $list[Constants::UNKOWN_ERROR], $param);
        }
        return $list;
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

            STR,
            [$args, 'tag' => "[{$tag} {$time}]"]
        );
        $file_src = $this->str("{root}/data/logs/{dir}/{file}.log", ["root" => $root, "dir" => date('Ym'), "file" => date('d')]);
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
