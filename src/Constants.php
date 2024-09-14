<?php

/**
 * +----------------------------------------------------------------------
 * | 常量
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

class Constants
{
    ////////////////////////////////////////////////// Common START //////////////////////////////////////////////////
    const SUCCESS = 0;
    const TIME_FULL = 'Y-m-d H:i:s', TIME_YMD = 'Y-m-d';
    const REQ_JSON = 0, REQ_GET = 1, REQ_POST = 2;
    const OK = 200, MOVED_PERMANENTLY = 301, UNAUTHORIZED = 401, FORBIDDEN = 403, NOT_FOUND = 404;
    //um单个文件上传;um编辑框;layui编辑器上传;editormd编辑器上传;baidu组件上传
    const UPLOAD_UMEDITOR_SINGLE = 0, UPLOAD_UMEDITOR_EDITOR = 1, UPLOAD_LAYUI_EDITOR = 2, UPLOAD_EDITORMD_EDITOR = 3, UPLOAD_WEB_UPLOADER = 4;
    const FILE_UPLOAD_SUCCESS = 0, FILE_SIZE_LIMIT = 100, FILE_UPLOAD_RESTRICTED = 200, FILE_TYPES_UNSUPPORTED = 300, FILE_NOT_FOUND = 400;
    const UNKOWN_ERROR = 999;
    const NL_CRLF2BR = 0, NL_BR2CRLF = 1;
    const OSS_SIZE_NORMAL = "", OSS_SIZE_MIDDLE = "m", OSS_SIZE_SMALL = "s";
    const TO_DBC = 0, TO_SBC=1;
    //////////////////////////////////////////////////  Common END  //////////////////////////////////////////////////

    ////////////////////////////////////////////////// Pdf START //////////////////////////////////////////////////
    // 横向
    const LANDSCAPE = 'landscape';
    // 纵向
    const PORTRAIT = 'portrait';
    //////////////////////////////////////////////////  Pdf END  //////////////////////////////////////////////////

    ////////////////////////////////////////////////// Env START //////////////////////////////////////////////////
    const ENV_PREFIX = 'PHP_';
    //////////////////////////////////////////////////  Env END  //////////////////////////////////////////////////

    ////////////////////////////////////////////////// Sitemap START //////////////////////////////////////////////////
    const SCHEMA_XMLNS = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    const SCHEMA_XMLNS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';
    const SCHEMA_XSI_SCHEMALOCATION = 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd';
    const DEFAULT_PRIORITY = 0.5;
    const SITEMAP_ITEMS = 50000;
    const SITEMAP_SEPERATOR = '-';
    const INDEX_SUFFIX = 'index';
    const SITEMAP_EXT = '.xml';
    //////////////////////////////////////////////////  Sitemap END  //////////////////////////////////////////////////

    ////////////////////////////////////////////////// Spider START //////////////////////////////////////////////////
    // 主机
    const HOST = 'http://www.chinabz.org/';
    //////////////////////////////////////////////////  Spider END  //////////////////////////////////////////////////

    ////////////////////////////////////////////////// TpCommon START //////////////////////////////////////////////////
    const V2 = "2.0";
    const V3 = "3.0";
    const V5 = "5.1";
    const V6 = "6.0";
    const V8 = "8.0";
    //////////////////////////////////////////////////  TpCommon END  //////////////////////////////////////////////////

    ////////////////////////////////////////////////// Office/Word START //////////////////////////////////////////////////
    // 字体大小。一号=26pt 二号=22pt 三号=16pt 四号=14pt 五号=10.5pt 六号=7.5pt
    const S1 = 26, S2 = 22, S3 = 16, S4 = 14, S5 = 10.5, S6 = 7.5;
    //////////////////////////////////////////////////  Office/Word END  //////////////////////////////////////////////////

    ////////////////////////////////////////////////// TpConsole/Command START //////////////////////////////////////////////////
    // 控制台输出
    const CONSOLE_WRITE = 0;
    // tp日志
    const LOG_WRITE = 1;
    // worker日志
    const STDOUT_WRITE = 2;
    // 带颜色输出
    const COLOR_ECHO = 3;
    //////////////////////////////////////////////////  TpConsole/Command END  //////////////////////////////////////////////////
}
