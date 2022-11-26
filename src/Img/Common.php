<?php
namespace Dfer\Tools\Img;

/**
 * +----------------------------------------------------------------------
 * | 图片处理常用的方法
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
class Common
{
     
    /**
					* 
     * 将图片对象保存到服务器
     *
     * @param  [Base64] $base64_image_content [要保存的Base64]
     * @param  [目录] $path [要保存的路径]
     */
    public function base64ImageContent($base64_image_content, $path)
    {
        $dir = "upload/base64/";
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
                 
    //获取html中的所有图片的地址，生成一个数组
    public function getImage($other, $arry = 0)
    {
        $preg = '/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i';
        //匹配img标签的正则表达式
        preg_match_all($preg, $other, $allImg);
        //这里匹配所有的img
        //var_dump($allImg);
        if ($arry) {
            return $allImg;
        } else {
            $rt = "";
            foreach ($allImg[1] as $item) {
                $rt = $rt . '"' . $item . '",';
            }
            $rt = substr($rt, 0, strlen($rt) - 1);
            return $rt;
        }
    }
	
    /*
     * 取得字符串中所有的图片地址
     *
     * $content		内容
     *
     * $order
     * All 所有图片
     * 0 第一张图片
     */
    public function getImgs($content, $order = 'ALL')
    {
        // 	 	var_dump($content);
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
                    *
     * 取得页面所有的图片地址
     *
     * $content
     * 网址
     *
     * $order
     * All 所有图片
     * 0 第一张图片
     */
    public function getImgsWeb($content, $order = 'ALL')
    {
        //获取网页内容
        $content = file_get_contents($content);
        // 	 	var_dump($content);
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
     * $imgsrc 原路径
     * $imgdst 目标路径
     * $imgwidth要改变的宽度
     * $imgheight要改变的高度
     * @return mixed
     **/
    public function resizeJpg($imgsrc, $imgdst, $imgWidth, $imgHeight)
    {
        $arr = getimagesize($imgsrc);
        //取得源图片的宽度、高度值
        function_exists('exif_imagetype') or die('请安装exif拓展');
        $imgType = exif_imagetype($imgsrc);
        //需要安装exif拓展
                
        //  echo $imgType;die();
        //gif
        if ($imgType == 1) {
            $imgsrc = imagecreatefromgif($imgsrc);
        //根据路径创建图片控件，需要安装gd拓展
        } elseif ($imgType == 2) {
            header("Content-type: image/jpg");
            // Create image and define colors
            $imgsrc = imagecreatefromjpeg($imgsrc);
        //根据路径创建图片控件
        }
        //png
        elseif ($imgType == 3) {
            header("Content-type: image/png");
            // Create image and define colors
            $imgsrc = imagecreatefrompng($imgsrc);
        //根据路径创建图片控件
        } else {
            die();
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
        //		$this -> log($arr[0] . $arr[1]);
                
        if ($imgType == 1) {
            imagegif($image, $imgdst);
        } elseif ($imgType == 2) {
            imagejpeg($image, $imgdst, 100);
        }
        //png
        elseif ($imgType == 3) {
            imagepng($image, $imgdst);
        }
        //imagejpeg($res,$file_name_dest, $quality);    //测试时必须另外定义一个文件路径才可以
        imagedestroy($image);
    }
}
