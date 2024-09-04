<?php

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
 *                 ......    .!%$! ..     | AUTHOR: dfer
 *         ......        .;o*%*!  .       | EMAIL: df_business@qq.com
 *                .:;;o&***o;.   .        | QQ: 3504725309
 *        .;;!o&****&&o;:.    ..          | WEBSITE: http://www.dfer.site
 * +----------------------------------------------------------------------
 *
 */

namespace Dfer\Tools;

use Dompdf\{Dompdf,Options};
use Dfer\Tools\Constants;

class Pdf extends Common
{
    /**
     * 将html转化为pdf
     * @param {Object} $var 变量
     **/
    public function html2pdf($html,$filename="pdf",$direction = Constants::PORTRAIT)
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html,'UTF-8');
        $dompdf->setPaper('A4', $direction);
        $dompdf->render();
        $filename = $this->str("{0}_{1}.pdf",[$filename,date('YmdHis')]);
        $dompdf->stream($filename);
    }
}
