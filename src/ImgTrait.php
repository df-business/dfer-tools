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
     * 将图片对象保存到服务器
     * @param {Object} $base64_image_content    要保存的Base64
     * @param {Object} $path    要保存的路径
     */
    public function base64ImageContent($base64_image_content, $path)
    {
        $dir = "data/upload/base64/";
        $err = 'error';
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];
            //      echo $new_file;
            if (!file_exists($dir)) {
                //检查是否有该文件夹，如果没有就创建，并给予读写权限
                mkdir($dir, 0700);
            }
            $new_file = $dir . $path . ".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                return '/' . $new_file;
            } else {
                return $err;
            }
        } else {
            return $err;
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
