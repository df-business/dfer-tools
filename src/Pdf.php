<?php
namespace Dfer\Tools;

use Dompdf\{Dompdf,Options};


/**
 * +----------------------------------------------------------------------
 * | pdf类
 * | composer require dompdf/dompdf
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
class Pdf extends Common
{
	private static $instance;
	
	// 横向
	const LANDSCAPE='landscape';
	// 纵向
	const PORTRAIT='portrait';

    /**
     * 防止外部实例化
     */
    private function __construct($config = [])
    {
    }
    /**
     * 防止外部克隆  
     */
    private function __clone()
    {
    }
    /**
     * 获取静态实例
     * 对当前类实例化一次之后，可以在任意位置复用，不需要再次实例化
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }


	/**
	 * 将html转化为pdf
	 * @param {Object} $var 变量
	 **/
	public function html2pdf($html,$filename="pdf",$direction = self::PORTRAIT)
	{
		$dompdf = new Dompdf();
		$dompdf->loadHtml($html,'UTF-8');
		$dompdf->setPaper('A4', $direction);
		$dompdf->render();
		$filename = $this->str("{0}_{1}.pdf",[$filename,date('YmdHis')]);
		$dompdf->stream($filename);
	}
}
