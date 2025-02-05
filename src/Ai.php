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

class Ai extends Common
{
    protected $api_key = '';
    protected $secret_key = '';
    private $debug = false;

    public function __construct($config = [], $needOssClient = true)
    {
        $this->api_key = $config['api_key'] ?? $this->api_key;
        $this->secret_key = $config['secret_key'] ?? $this->secret_key;

        $this->debug = $config['debug'] ?? $this->debug;
    }

    // ###################################### baidu START ######################################
    // ********************** 千帆 START **********************
    /**
     * 运行
     * 千帆 ModelBuilder
     * https://console.bce.baidu.com/qianfan/overview
     * https://console.bce.baidu.com/support/?u=doc-inner&timestamp=1738743405063#/api?product=QIANFAN&project=%E5%8D%83%E5%B8%86ModelBuilder&parent=ERNIE%204.0&api=rpc%2F2.0%2Fai_custom%2Fv1%2Fwenxinworkshop%2Fchat%2Fcompletions_pro&method=post
     * @param object $var 变量
     * @return Array
     **/
    public function runBdQf($var = null)
    {
        $url = "https://aip.baidubce.com/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions_pro?access_token={$this->getTokenBdQf()}";
        $data = [
            'messages' => [
                [
                    "role" => "user",
                    "content" => "生成100字的政府报告"
                ]
            ]
        ];
        $result = $this->httpRequest($url, $data, Constants::REQ_JSON);
        return $result;
    }

    /**
     * 获取token
     * @param object $var 变量
     * @return String
     **/
    public function getTokenBdQf($var = null)
    {
        $url = "https://aip.baidubce.com/oauth/2.0/token";
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->api_key,
            'client_secret' => $this->secret_key
        ];
        $result = $this->httpRequest($url, $data);
        // var_dump($result);
        return $result['access_token'];
    }
    // **********************  千帆 END  **********************
    // ######################################  baidu END  ######################################

    // ###################################### 火山引擎 START ######################################
    /**
     * 运行
     * 火山引擎
     * https://www.volcengine.com/
     * https://www.volcengine.com/docs/82379/1099455
     * @param object $var 变量
     * @return Array
     **/
    public function runVe($var = null)
    {
        $url = "https://ark.cn-beijing.volces.com/api/v3/chat/completions";
        $header = [
            'Authorization' => "Bearer {$this->api_key}"
        ];
        $data = [
            'messages' => [
                [
                    "role" => "user",
                    "content" => "生成100字的政府报告",
                ]
            ],
            "model" => "ep-20250121132129-jxqnz"
        ];
        $result = $this->httpRequest($url, $data, Constants::REQ_JSON, $header);
        return $result;
    }
    // ######################################  火山引擎 END  ######################################

    // ###################################### DeepSeek START ######################################
    /**
     * 运行
     * 深度求索
     * https://www.deepseek.com/
     * https://api-docs.deepseek.com/zh-cn/api/create-chat-completion
     * @param object $var 变量
     * @return Array
     **/
    public function runDs($var = null)
    {
        $url = "https://api.deepseek.com/chat/completions";
        $header = [
            'Authorization' => "Bearer {$this->api_key}"
        ];
        $data = [
            'messages' => [
                [
                    "role" => "user",
                    "content" => "生成100字的政府报告",
                ]
            ],
            "model" => "deepseek-chat"
        ];
        $result = $this->httpRequest($url, $data, Constants::REQ_JSON, $header);
        return $result;
    }
    // ######################################  DeepSeek END  ######################################

}
