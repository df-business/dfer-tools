<?php

/**
 * +----------------------------------------------------------------------
 * | 图片处理
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

trait ImgTrait
{
    /**
     * 保存Base64编码的图片
     * @param String $base64String
     */
    public function saveBase64Image($base64String)
    {
        // 检查是否是 Base64 编码的图片
        if (preg_match('/^data:(\w+)\/(\w+);base64,/', $base64String, $matches)) {
            $file_type = $matches[1];
            $file_ext = $matches[2];

            // 保存路径
            $saveDir = "upload" . DIRECTORY_SEPARATOR . "base64" . DIRECTORY_SEPARATOR . $file_type . DIRECTORY_SEPARATOR . $this->getTime(null, "Y") . DIRECTORY_SEPARATOR . $this->getTime(null, "m");
            $this->mkDirs($saveDir);
            $outputFilePath = $this->str("{0}/{1}.{2}", [$saveDir, $this->generateShortUUID() . '.' . date("dHis"), $file_ext]);

            // 去掉 Base64 字符串的前缀
            $base64Data = substr($base64String, strpos($base64String, ',') + 1);
            // 解码 Base64 字符串
            $imageData = base64_decode($base64Data);

            if ($imageData !== false) {
                // 将图片数据保存到指定位置
                if (file_put_contents($outputFilePath, $imageData)) {
                    return $outputFilePath;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 保存网络图片到本地
     * @param String $imageUrl 图片url
     */
    function saveImageFromUrl($imageUrl)
    {

        // 获取图片数据
        $imageData = file_get_contents($imageUrl);
        if ($imageData === false) {
            return "无法获取网络图片";
        }
        $imageType = $this->getExt($imageUrl);

        // 保存路径
        $saveDir = "upload" . DIRECTORY_SEPARATOR . "url" . DIRECTORY_SEPARATOR . "image" . DIRECTORY_SEPARATOR . $this->getTime(null, "Y") . DIRECTORY_SEPARATOR . $this->getTime(null, "m");
        $this->mkDirs($saveDir);
        $outputFilePath = $this->str("{0}/{1}.{2}", [$saveDir, $this->generateShortUUID() . '.' . date("dHis"), $imageType]);

        // 保存图片
        if (file_put_contents($outputFilePath, $imageData)) {
            return $outputFilePath;
        } else {
            return false;
        }
    }

    /**
     * 取得字符串中所有的图片地址
     *
     * @param {Object} $content    内容或网址
     * @param {Object} $order    All 所有图片 0 第一张图片
     */
    public function getImgs($content, $order = 'ALL')
    {
        // var_dump($content);
        if (substr($content, 0, 5) == 'http:') {
            //获取网页内容
            $content = file_get_contents($content);
        }
        $pattern = "/<img.*?src=[\'|\"](.*?(?:[\.gif|\.jpg\.png\.JPG]))[\'|\"].*?[\/]?>/";
        preg_match_all($pattern, $content, $match);

        if (isset($match[1]) && !empty($match[1])) {
            if ($order === 'ALL') {
                return $match[1];
            }
            if (is_numeric($order) && isset($match[1][$order])) {
                return $match[1][$order];
            }
        }
        return '';
    }

    /**
     * 创建图片对象
     * 只对有正常物理路径的图片有效
     * 对于缓存类图片无效
     */
    public function createImage($img)
    {
        $ext = strtolower(substr($img, strrpos($img, '.')));
        if ($ext == '.png') {
            $thumb = imagecreatefrompng($img);
        } elseif ($ext == '.gif') {
            $thumb = imagecreatefromgif($img);
        } else {
            $thumb = imagecreatefromjpeg($img);
        }
        return $thumb;
    }

    /**
     * 改变图片大小
     * 依照像素进行转化
     * @param {Object} $imgsrc    原路径
     * @param {Object} $imgdst    目标路径
     * @param {Object} $imgWidth    要改变的宽度
     * @param {Object} $imgHeight    要改变的高度
     * @return None
     */
    public function resizeJpg($imgsrc, $imgdst, $imgWidth, $imgHeight)
    {
        //取得源图片的宽度、高度值
        $arr = getimagesize($imgsrc);
        function_exists('exif_imagetype') or die('请安装exif拓展');
        $imgType = exif_imagetype($imgsrc);

        if ($imgType == 1) {
            //gif
            $imgsrc = imagecreatefromgif($imgsrc);
            //根据路径创建图片控件，需要安装gd拓展
        } elseif ($imgType == 2) {
            // header("Content-type: image/jpg");
            $imgsrc = imagecreatefromjpeg($imgsrc);
        } elseif ($imgType == 3) {
            // header("Content-type: image/png");
            $imgsrc = imagecreatefrompng($imgsrc);
        } else {
            die('未知的图片类型');
        }
        //  $imgsrc=this->imagecreatefrompng();
        $image = imagecreatetruecolor($imgWidth, $imgHeight);

        if ($imgType == 3) {
            $color = imagecolorallocate($image, 255, 255, 255);
            imagefill($image, 0, 0, $color);
            imagecolortransparent($image, $color);
        }
        //根据宽高创建一个彩色的底图，作为目标图像
        imagecopyresampled($image, $imgsrc, 0, 0, 0, 0, $imgWidth, $imgHeight, $arr[0], $arr[1]);

        if ($imgType == 1) {
            imagegif($image, $imgdst);
        } elseif ($imgType == 2) {
            imagejpeg($image, $imgdst, 100);
        }
        //png
        elseif ($imgType == 3) {
            imagepng($image, $imgdst);
        }
        //测试时必须另外定义一个文件路径才可以
        //imagejpeg($res,$file_name_dest, $quality);
        imagedestroy($image);
    }

    /**
     * 判断网络图片是否存在
     * @param {Object} $url    地址。eg:http://res.tye3.com/ktp_tye3/2024/video/c/x2BZYk3zz7h24dXR.jpg
     */
    public function imageExists($url)
    {
        $imageInfo = @getimagesize($url);
        return $imageInfo !== false;
    }
}
