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

    // ********************** 获取目录所有文件 START **********************

    // 扫描结果
    static $scan_result = [];

    /**
     * 遍历目录，获取文件数组
     * @param String $path 路径名
     * @param Array $exclude_dirs 排除的目录名。eg:["123"]
     * @param Array $exclude_files 排除的文件名。eg:["1.php"]
     * @return mixed
     */
    public function scanDir($path, $exclude_dirs = [], $exclude_files = [])
    {
        if ($handle = opendir($path)) {
            while (($name = readdir($handle)) !== false) {
                if ($name != ".." && $name != ".") {
                    $path_new = "{$path}/{$name}";
                    if (is_dir($path_new)) {
                        // 目录
                        if (in_array($name, $exclude_dirs)) continue;
                        $this->scanDir($path_new);
                    } else {
                        // 文件
                        if (in_array($name, $exclude_files)) continue;
                        self::$scan_result[$name] = $path_new;
                    }
                }
            }
            closedir($handle);
        }
    }

    /**
     * 获取扫描结果
     * @return Array 文件数组
     */
    public function getScanResult()
    {
        $files = self::$scan_result;
        // 重置文件数组
        self::$scan_result = [];
        return $files;
    }

    /**
     * 自动加载类、库或配置文件
     * @param String $directory 目录路径
     * @param Array $exclude_dirs 排除的目录名。eg:["123"]
     * @param Array $exclude_files 排除的文件名。eg:["1.php"]
     * @return Bool true 成功 false 失败
     **/
    public function autoloadPhpFilesFromDirectory($directory, $exclude_dirs = [], $exclude_files = [])
    {
        $this->scanDir($directory, $exclude_dirs, $exclude_files);
        $paths = $this->getScanResult();
        // var_dump($directory,$paths);return ;
        if ($paths) {
            // 循环遍历目录中的文件
            foreach ($paths as $name => $path) {
                // 检查文件扩展名是否为 .php
                if (pathinfo($name, PATHINFO_EXTENSION) === 'php') {
                    require_once $path;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 遍历一个目录下的所有文件和文件夹
     * @param String $dir 路径名
     * @return Array 文件树状图数组
     */
    public function getTreeFromDir($dir)
    {
        $list_tree = [];
        $list = scandir($dir);
        foreach ($list as $key => $name) {
            if ($name == '..' || $name == '.') {
                continue;
            }
            if (is_file("{$dir}{$name}")) {
                // 文件
                $list_tree[] = [
                    'name' => $name,
                    'src' => "{$dir}{$name}"
                ];
            } else {
                // 目录
                $list_new = $this->getTreeFromDir("{$dir}{$name}/");
                $list_tree[] = [
                    'name' => $name,
                    'src' => "{$dir}{$name}",
                    'children' => $list_new,
                ];
            }
        }
        return $list_tree;
    }

    /**
     * 获取目录中文件修改时间最新的一个文件的文件名
     * @param String $directory 目录路径
     * @return String 文件名
     **/
    public function getNewestFileFromDir($directory)
    {
        $latestFile = '';
        $latestTime = 0;

        foreach (scandir($directory) as $file) {
            if (in_array($file, ['.', '..'])) {
                // 跳过当前目录和上级目录
                continue;
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



    // **********************  获取目录所有文件 END  **********************

    /**
     * 删除目录和目录下的文件，成功则返回1
     * @param String $dir    目录的物理路径
     * @param Bool $quiet    静默操作
     */
    public function delDir($dir, $quiet = true)
    {
        try {
            if (is_dir($dir)) {
                //先删除目录下的文件：
                $dh = opendir($dir);
                while ($file = readdir($dh)) {
                    if ($file != "." && $file != "..") {
                        $fullpath = $dir . "/" . $file;
                        if (!is_dir($fullpath)) {
                            if (!$quiet)
                                echo "删除 {$fullpath}\n";
                            $rt = unlink($fullpath);
                            if (!$rt) {
                                return false;
                            }
                        } else {
                            //循环删除文件
                            $this->deldir($fullpath);
                        }
                    }
                }
                closedir($dh);
                //删除当前文件夹
                if (!$quiet)
                    echo "删除 {$dir}\n";
                return rmdir($dir);
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        } catch (Throwable  $e) {
            return false;
        }
    }

    /**
     * 删除目录
     * @param String $dir
     * @param Bool $quiet    静默操作
     */
    public function deleteDir($dir, $quiet = true)
    {
        return $this->delDir($dir, $quiet);
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
     * @param String $strSrcDir    原始目录
     * @param String $strDstDir    目标目录
     * @param Bool $quiet    静默操作
     */
    public function copyDir($strSrcDir, $strDstDir, $quiet = true)
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
                echo "目录 {$file}\n";
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
     * 通用复制
     * @param String $strSrc    原始路径
     * @param String $strDst    目标路径
     * @param Bool $quiet    静默操作
     **/
    public function copy($strSrc, $strDst, $quiet = true)
    {
        if (is_dir($strSrc)) {
            return $this->copyDir($strSrc, $strDst, $quiet);
        } else {
            if (!$quiet)
                echo "文件 {$strSrc}=>{$strDst}\n";
            if (!copy($strSrc, $strDst)) {
                return false;
            }
        }
        return true;
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
     * 输出调试信息到日志文件
     * @param Object 自动获取所有参数
     **/
    public function debug()
    {
        // 获取此方法的调用来源
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        if (isset($trace[1]['function']) && $trace[1]['function'] === '__call') {
            $args_origin = func_get_args();
            // 第一个参数
            $file_name = $args_origin[0];
            $file_name = date('d') . ".{$file_name}";
            // 第二个及之后的所有参数（如果存在）
            $args_new = array_slice($args_origin, 1);
            $args = $this->str($args_new);
        } else {
            $file_name = date('d');
            $args = $this->str(func_get_args());
        }

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
        $file_src = $this->str("{root}/data/logs/{dir}/{file}.log", ["root" => $root, "dir" => date('Ym'), "file" => $file_name]);
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

    /**
     * 读取配置文件的参数值
     * @param {Object} $path 文件路径
     * @param {Object} $key 参数名
     * @param {Object} $index 编号
     */
    public function getConfigParam($path, $key, $index = 2)
    {
        $str = $this->readFile($path);
        $pattern = "/('{$key}'\s*=>\s*)(\d+)(,)/";
        preg_match($pattern, $str, $matches);
        // var_dump($matches);
        if ($index === null) {
            return $matches;
        }
        return $matches[$index] ?? 0;
    }

    /**
     * 设置配置文件的参数值
     * @param {Object} $path 文件路径
     * @param {Object} $key 参数名
     * @param {Object} $value 参数名
     */
    public function setConfigParam($path, $key, $value)
    {
        $str = $this->readFile($path);
        $list = $this->getConfigParam($path, $key, null);
        $ori = $list[0];
        unset($list[0]);
        $list[2] = $value;
        $new_str = $this->strReplace($str, [$ori], [implode('', $list)]);
        return $this->writeFile($new_str, $path);
    }

    /**
     * 记录网站请求里的agent信息
     * @param String $site_root 日志保存目录
     */
    public function agentWrite($site_root = 'api.dfer.site')
    {
        // var_dump($_SERVER);
        $accept = strtolower($_SERVER["HTTP_ACCEPT"]??'--'?:'--');
        $user_agent = strtolower($_SERVER["HTTP_USER_AGENT"]??'--'?:'--');
        $host = $_SERVER['HTTP_HOST']??'--'?:'--';
        $remote_addr = $_SERVER['REMOTE_ADDR']??'--'?:'--';
        if (!empty($_SERVER)) {
            // 项目根目录
            $root = "/www/wwwroot/{$site_root}";
            $str = $accept . PHP_EOL . $user_agent . PHP_EOL . $host . PHP_EOL . $remote_addr . PHP_EOL . PHP_EOL;
            // 记录当天的请求
            $file_src = $this->str("{root}/data/agent/{file}.log", ["root" => $root, "file" => date('Ymd')]);
            $this->writeFile($str, $file_src, "a");
        }
    }

    /**
     * 读取agent记录
     */
    public function agentRead()
    {
        // 项目根目录
        $root = "/www/wwwroot/api.dfer.site";
        // 获取昨天的记录
        $time = date('Ymd', strtotime('-1 day'));
        $file_src = $this->str("{root}/data/agent/{file}.log", ["root" => $root, "file" =>  $time]);
        $str = $this->readFile($file_src);
        // var_dump($file_src,$str);
        $result = [];
        if ($str !== false) {
            $list = explode(PHP_EOL . PHP_EOL, $str);
            foreach ($list as $key => $value) {
                $item = explode(PHP_EOL, $value);
                // var_dump($item);
                if (count($item) > 1) {
                    $result[] = [
                        'accept' => $item[0],
                        'user_agent' => $item[1],
                        'host' => $item[2],
                        'remote_addr' => $item[3],
                        'time' => $time
                    ];
                }
            }
        }

        return $result;
    }
}
