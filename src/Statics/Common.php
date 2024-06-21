<?php

namespace Dfer\Tools\Statics;

/**
 * +----------------------------------------------------------------------
 * | 静态调用
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
class Common extends Base
{

    const TIME_FULL = 'Y-m-d H:i:s', TIME_YMD = 'Y-m-d';

    const REQ_JSON = 0, REQ_GET = 1, REQ_POST = 2;

    const OK=200,MOVED_PERMANENTLY=301,UNAUTHORIZED=401,FORBIDDEN=403,NOT_FOUND=404;

    //um单个文件上传;um编辑框;layui编辑器上传;editormd编辑器上传;baidu组件上传
    const UPLOAD_UMEDITOR_SINGLE = 0,UPLOAD_UMEDITOR_EDITOR = 1,UPLOAD_LAYUI_EDITOR = 2,UPLOAD_EDITORMD_EDITOR = 3,UPLOAD_WEB_UPLOADER = 4;

    const NL_CRLF2BR= 0, NL_BR2CRLF = 1;

    const OSS_SIZE_NORMAL="",OSS_SIZE_MIDDLE="m",OSS_SIZE_SMALL="s";

    protected function className(){
        return str_replace("\Statics", "", __CLASS__);
    }
}
