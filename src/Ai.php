<?php

/**
 * +----------------------------------------------------------------------
 * | AI集成
 * | 包含主流的AI接口，如：百度、火山引擎、DeepSeek
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

use Dfer\Tools\Constants;
use Dfer\Tools\Statics\Storage;

class Ai extends Common
{
    protected $api_key = '';
    protected $secret_key = '';
    private $debug = false;
    static $token = '';

    public function __construct($config = [], $needOssClient = true)
    {
        $this->api_key = $config['api_key'] ?? $this->api_key;
        $this->secret_key = $config['secret_key'] ?? $this->secret_key;

        $this->debug = $config['debug'] ?? $this->debug;
    }

    // ###################################### baidu START ######################################

    /**
     * 运行
     * 千帆 ModelBuilder
     * https://console.bce.baidu.com/qianfan/overview
     * @param object $var 变量
     * @return Array
     **/
    public function runBdQf($command, $type = Constants::AI_TEXT)
    {
        $this->setTokenBdQf();
        switch ($type) {
            case Constants::AI_TEXT:
            default:
                // ERNIE-4.0-8K https://console.bce.baidu.com/support/?u=doc-inner&timestamp=1738743405063#/api?product=QIANFAN&project=%E5%8D%83%E5%B8%86ModelBuilder&parent=ERNIE%204.0&api=rpc%2F2.0%2Fai_custom%2Fv1%2Fwenxinworkshop%2Fchat%2Fcompletions_pro&method=post
                $url = "https://aip.baidubce.com/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions_pro?access_token=" . static::$token;
                $data = [
                    'messages' => [
                        [
                            "role" => "user",
                            "content" => $command
                        ]
                    ]
                ];
                break;
        }
        $result = $this->httpRequest($url, $data, Constants::REQ_JSON);
        return $result;
    }

    /**
     * 设置token
     * @param object $var 变量
     * @return mixed
     **/
    private function setTokenBdQf($var = null)
    {
        if (empty(static::$token)) {
            $token = Storage::store('bd_qf_token');
            if (!$token) {
                $url = "https://aip.baidubce.com/oauth/2.0/token";
                $data = [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->api_key,
                    'client_secret' => $this->secret_key
                ];
                $result = $this->httpRequest($url, $data);
                $expires_in = $result['expires_in'];
                $token = $result['access_token'];
                Storage::store('bd_qf_token', $token, $expires_in);
                // var_dump($result);die;
            }
            static::$token = $token;
        }
    }

    // ######################################  baidu END  ######################################

    // ###################################### 火山引擎 START ######################################
    /**
     * 运行
     * https://www.volcengine.com/
     * @param object $var 变量
     * @return Array
     **/
    public function runVe($command, $type = Constants::AI_TEXT)
    {
        switch ($type) {
            case Constants::AI_TEXT:
            default:
                // 文本生成 https://www.volcengine.com/docs/82379/1298454
                $url = "https://ark.cn-beijing.volces.com/api/v3/chat/completions";
                $data = [
                    'messages' => [
                        [
                            "role" => "user",
                            "content" => $command,
                        ]
                    ],
                    "model" => "ep-20250121132129-jxqnz"
                ];
                break;
        }
        $header = [
            'Authorization' => "Bearer {$this->api_key}"
        ];
        $result = $this->httpRequest($url, $data, Constants::REQ_JSON, $header);
        return $result;
    }
    // ######################################  火山引擎 END  ######################################

    // ###################################### DeepSeek START ######################################
    /**
     * 运行
     * https://www.deepseek.com/
     * @param object $var 变量
     * @return Array
     **/
    public function runDs($command, $type = Constants::AI_TEXT)
    {
        switch ($type) {
            case Constants::AI_TEXT:
            default:
                // 对话补全 https://api-docs.deepseek.com/zh-cn/api/create-chat-completion
                $url = "https://api.deepseek.com/chat/completions";
                $data = [
                    'messages' => [
                        [
                            "role" => "user",
                            "content" => $command,
                        ]
                    ],
                    "model" => "deepseek-chat",
                ];
                break;
        }
        $header = [
            'Authorization' => "Bearer {$this->api_key}"
        ];
        $result = $this->httpRequest($url, $data, Constants::REQ_JSON, $header, null, 9);
        return $result;
    }
    // ######################################  DeepSeek END  ######################################

}
