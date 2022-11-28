<?php
namespace Dfer\Tools;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

use think\Config;


/**
 * +----------------------------------------------------------------------
 * | 生成二维码
 * | composer require endroid/qr-code
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
class QrCode extends Common
{
 
    /**
     * 自动初始化
     */
    public function __construct()
    {
    }


    /**
     * 获取base64
     *
     */
    public function base64($url)
    {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            // ->data('Custom QR code contents')
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            // ->logoPath(app()->getRootPath().'/public/static/logo.gif')
            // ->labelText('This is the label')
            // ->labelFont(new NotoSans(20))
            // ->labelAlignment(new LabelAlignmentCenter())
            ->build();
            
        // base64
        $dataUri = $result->getDataUri();
        
        return $dataUri;
    }
}
