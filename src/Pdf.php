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
	// 横向
	const LANDSCAPE='landscape';
	// 纵向
	const PORTRAIT='portrait';

    public function __construct()
    {
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
