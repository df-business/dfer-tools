<?php

/**
 * +----------------------------------------------------------------------
 * | 生成二维码
 * | https://github.com/endroid/qr-code
 * |
 * | composer require endroid/qr-code
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

namespace Dfer\Tools;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\{ErrorCorrectionLevelHigh, ErrorCorrectionLevelQuartile};
use Endroid\QrCode\RoundBlockSizeMode\{RoundBlockSizeModeMargin, RoundBlockSizeModeEnlarge, RoundBlockSizeModeShrink};
use Endroid\QrCode\Label\Alignment\{LabelAlignmentCenter};
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Label\Font\{NotoSans, OpenSans};
use Endroid\QrCode\Color\Color;

class QrCode extends Common
{

    protected static $instance;

    public static function instance()
    {
        if (is_null(self::$instance)) {

            self::$instance = Builder::create()
                // 图片参数
                ->writer(new PngWriter())
                ->writerOptions([])
                // 内容编码
                ->encoding(new Encoding('UTF-8'))
                // 容错等级(越高二维码越密集)
                ->errorCorrectionLevel(new ErrorCorrectionLevelQuartile)
                // 验证读取器(默认情况下禁用)
                ->validateResult(false);
        }

        return self::$instance;
    }

    /**
     * 设置样式
     * @param {Object} int $size    二维码内容区域大小
     * @param {Object} int $margin    二维码外间距
     * @param {Object} $mode    二维码内容尺寸模式
     * @param {Object} Color $frontColor    前景颜色
     * @param {Object} Color $bgColor    背景颜色
     */
    public function setStyle(int $size = 300, int $margin = 10, Color $bgColor = null, $mode = null, Color $fgColor = null)
    {
        self::instance()
            ->size($size)
            ->margin($margin)
            ->roundBlockSizeMode($mode ?: new RoundBlockSizeModeMargin)
            ->foregroundColor($fgColor ?: new Color(0, 0, 0))
            ->backgroundColor($bgColor ?: new Color(255, 255, 255));
        return $this;
    }

    /**
     * 设置二维码内容
     * @param {Object} string $data    文字或者网址
     */
    public function setData(string $data)
    {
        self::instance()->data($data);
        return $this;
    }


    /**
     * 二维码下方文字
     * @param {Object} string $text
     * @param {Object} Color $color
     * @param {Object} $font    字体样式
     * @param {Object} $align    对齐方式
     */
    public function setText(string $text, Color $color = null, $font = null, $align = null)
    {
        self::instance()
            ->labelText($text)
            ->labelTextColor($color ?: new Color(255, 0, 0))
            ->labelFont($font ?: new NotoSans(20))
            ->labelAlignment($align ?: new LabelAlignmentCenter);
        return $this;
    }

    /**
     * 二维码中间区域logo图片
     * @param {Object} string $path    本地图片或者网络图片
     * @param {Object} int $width
     * @param {Object} int $height
     */
    public function setLogo(string $path = 'https://oss.dfer.site/df_icon/130x130.png', int $width = 100, int $height = 100)
    {
        self::instance()
            ->logoPath($path)
            ->logoResizeToWidth($width)
            ->logoResizeToHeight($height);
        return $this;
    }

    /**
     * 直接输出在浏览器中
     * @param {Object} int $max_age 文件缓存时间（秒）
     */
    public function getFile(int $max_age = 60)
    {
        $result = self::instance()->build();
        //处理在TP框架中显示乱码问题
        ob_end_clean();
        header('Content-Type: ' . $result->getMimeType());
        // 浏览器缓存（秒）
        header("Cache-Control:max-age={$max_age}");
        echo $result->getString();
        exit;
    }

    /**
     * 将二维码图片保存到本地服务器
     * @param {Object} string $fileName    文件名称
     * @return {Object} string 保存路径
     */
    public function saveFile(string $fileName = 'qrcode.png')
    {
        // 执行生成器
        $result = self::instance()->build();
        $file = $this->getFileName($fileName);
        $result->saveToFile($file);
        return '/' . $file;
    }

    /**
     * 获取base64数据流
     * @return {Object} string base64字符串
     */
    public function saveStream()
    {
        $result = self::instance()->build();
        $dataUri = $result->getDataUri();
        return $dataUri;
    }

    /**
     * 获取文件路径和名称
     * @param {Object} string $fileName
     */
    protected function getFileName(string $fileName, string $dir = "storage/qr_code")
    {
        // php-51 开始支持静态调用
        $path =  PHP_VERSION_ID >= 51000 ? Env::get('QR_CODE.SAVE_PATH', $dir) : $dir;
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return $path . "/" . $fileName;
    }
}
