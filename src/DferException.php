<?php

/**
 * +----------------------------------------------------------------------
 * | 自定义 异常类
 * | 如：
 * |    try {throw new DferException('Redis认证失败', 1001, $errorData);} catch (DferException $e) {$message = $e->getMessage();$code = $e->getCode();$data = $e->getErrorData();}
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

use DOMDocument, Closure, Exception, Error, Throwable, DateTime, stdClass;

class DferException extends Exception {
    private $errorData;

    public function __construct($message, $code = 0, $data = [], Exception $previous = null) {
        $this->errorData = $data;

        // 如果提供了数组数据，将其转换为消息字符串
        if (is_array($message)) {
            $message = $this->formatArrayMessage($message);
        }

        parent::__construct($message, $code, $previous);
    }

    private function formatArrayMessage(array $data): string {
        // 将数组格式化为可读的字符串
        $parts = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $parts[] = "{$key}: {$value}";
        }
        return implode(' | ', $parts);
    }

    public function getErrorData() {
        return $this->errorData;
    }

    // 覆盖 __toString 方法
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n" .
               "附加数据: " . json_encode($this->errorData, JSON_UNESCAPED_UNICODE);
    }
}
