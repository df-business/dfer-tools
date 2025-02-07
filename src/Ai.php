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

use Dfer\Tools\{Constants, AiCommand};
use Dfer\Tools\Statics\Storage;

class Ai extends Common
{
    // 调试
    private $debug = false;
    // 全局key
    protected $api_key_bd_qf = '';
    protected $api_key_ve = '';
    protected $api_key_ds = '';
    // 应用key
    protected $app_api_key_bd_qf = '';
    protected $app_secret_key_bd_qf = '';

    protected static $instances = [];

    public function __construct($config = [])
    {
        $this->api_key_bd_qf = $config['api_key_bd_qf'] ?? $this->api_key_bd_qf;
        $this->api_key_ve = $config['api_key_ve'] ?? $this->api_key_ve;
        $this->api_key_ds = $config['api_key_ds'] ?? $this->api_key_ds;

        $this->app_api_key_bd_qf = $config['app_api_key_bd_qf'] ?? $this->app_api_key_bd_qf;
        $this->app_secret_key_bd_qf = $config['app_secret_key_bd_qf'] ?? $this->app_secret_key_bd_qf;

        $this->debug = $config['debug'] ?? $this->debug;

        // var_dump($config);
    }

    /**
     * 运行指令
     *
     * $rtn = $ai->command(AiCommand::ROLE_LIBAI,['白兄好久不见']);
     * $rtn = $ai->command(AiCommand::IMG_PORTRAIT,['中国唐朝古风，写实','云想衣裳花想容，春风拂槛露华浓'],Constants::AI_IMAGE,Constants::AI_BD_QF);
     *
     * @param String $tmpl 模板。
     * @param Array $data 模板参数。
     * @param Int $type 指令类型。Constants::AI_TEXT 文本 | Constants::AI_IMAGE 图片
     * @param Int $ai_type AI类型。Constants::AI_BD_QF 千帆 | Constants::AI_VE 火山引擎 | Constants::AI_DS 深度求索（默认）
     * @return mixed
     **/
    public function command($tmpl, $data, $type = Constants::AI_TEXT, $ai_type = Constants::AI_DS)
    {
        $command = $this->format($tmpl, $data);
        $result = $this->run($command, $type, $ai_type);
        return $result;
    }

    /**
     * 自动运行指令
     * 超时以后，自动切换其余的AI
     * @param object $var 变量
     * @return mixed
     **/
    public function commandAuto($tmpl, $data, $type = Constants::AI_TEXT)
    {
        $command = $this->format($tmpl, $data);
        $result = $this->runAuto($command, $type);
        return $result;
    }

    /**
     * 运行AI
     *
     * $rtn = $ai->run("你好");
     * $rtn = $ai->run("你好",Constants::AI_TEXT,Constants::AI_BD_QF);
     * $rtn = $ai->run("你好",Constants::AI_TEXT,Constants::AI_VE);
     * $rtn = $ai->run("你好",Constants::AI_TEXT,Constants::AI_DS);
     *
     * $rtn = $ai->run("剑来",Constants::AI_IMAGE,Constants::AI_BD_QF);
     *
     * @param String $command 指令内容
     * @param Int $type 指令类型。Constants::AI_TEXT 文本 | Constants::AI_IMAGE 图片
     * @param Int $ai_type AI类型。Constants::AI_BD_QF 千帆 | Constants::AI_VE 火山引擎 | Constants::AI_DS 深度求索（默认）
     * @return Array
     **/
    public function run($command, $type = Constants::AI_TEXT, $ai_type = Constants::AI_DS)
    {
        switch ($ai_type) {
            case Constants::AI_BD_QF:
                $result = $this->runBdQf($command, $type);
                break;
            case Constants::AI_VE:
                $result = $this->runVe($command, $type);
                break;
            case Constants::AI_DS:
                $result = $this->runDs($command, $type);
            default:
                break;
        }
        return $result ?: Constants::AI_TIMEOUT;
    }

    /**
     * 自动运行AI
     * 超时以后，自动切换其余的AI
     * @param object $var 变量
     * @return mixed
     **/
    public function runAuto($command, $type = Constants::AI_TEXT)
    {
        $result = $this->runDs($command, $type);
        if (!$result) {
            $result = $this->runVe($command, $type);
        }
        if (!$result) {
            $result = $this->runBdQf($command, $type);
        }
        return $result;
    }

    /**
     * 控制台命令
     * Windows下打开链接。自动用浏览器打开图片地址
     * @param String $url 地址
     * @return mixed
     **/
    public function openUrl($url = null)
    {
        $command = "start \"\" \"$url\"";
        exec($command);
    }

    /**
     * 重写父级方法
     */
    public function debug()
    {
        if ($this->debug)
            parent::debug(func_get_args());
    }

    public function httpRequest($url, $data = null, $type = Constants::REQ_POST, $header = null, $cookie = null, $timeout = 30)
    {
        $result = parent::httpRequest($url, $data, $type, $header, $cookie, $timeout);
        $this->debug($result);
        return $result;
    }

    // ###################################### baidu START ######################################

    /**
     * 运行
     * 千帆 ModelBuilder
     * https://console.bce.baidu.com/qianfan/modelcenter/model/buildIn/list
     * @param String $command 指令内容
     * @param Int $type 指令类型
     * @return Array
     **/
    public function runBdQf($command, $type = Constants::AI_TEXT)
    {
        switch ($type) {
            case Constants::AI_IMAGE:
                // 图像 https://cloud.baidu.com/doc/WENXINWORKSHOP/s/zm696hdfq
                $url = "https://qianfan.baidubce.com/v2/images/generations";
                $data = [
                    "model" => "irag-1.0",
                    // 生成图片的描述。长度不超过200个字符
                    "prompt" => $command
                ];
                $header = [
                    'Authorization' => "Bearer {$this->api_key_bd_qf}"
                ];
                $result = $this->httpRequest($url, $data, Constants::REQ_JSON, $header ?? null);
                $result = $result['data'][0]['url'] ?? false;
                break;
            case Constants::AI_TEXT:
            default:
                // ERNIE-4.0-8K https://cloud.baidu.com/doc/WENXINWORKSHOP/s/clntwmv7t
                $this->initTokenBdQf();
                $url = "https://aip.baidubce.com/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions_pro?access_token=" . static::$instances['token'];
                $data = [
                    'messages' => [
                        [
                            "role" => "user",
                            "content" => $command
                        ]
                    ]
                ];
                $result = $this->httpRequest($url, $data, Constants::REQ_JSON, $header ?? null);
                $result = $result['result'] ?? false;
                break;
        }
        return $result;
    }

    /**
     * 设置token
     * @param object $var 变量
     * @return mixed
     **/
    private function initTokenBdQf($var = null)
    {
        if (empty(static::$instances['token'])) {
            $token = Storage::store('bd_qf_token');
            if (!$token) {
                $url = "https://aip.baidubce.com/oauth/2.0/token";
                $data = [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->app_api_key_bd_qf,
                    'client_secret' => $this->app_secret_key_bd_qf
                ];
                $result = $this->httpRequest($url, $data);
                $expires_in = $result['expires_in'];
                $token = $result['access_token'];
                Storage::store('bd_qf_token', $token, $expires_in);
                // var_dump($result);die;
            }
            static::$instances['token'] =  $token;
        }
    }

    // ######################################  baidu END  ######################################

    // ###################################### 火山引擎 START ######################################
    /**
     * 运行
     * https://www.volcengine.com/
     * @param String $command 指令内容
     * @param Int $type 指令类型
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
            'Authorization' => "Bearer {$this->api_key_ve}"
        ];
        $result = $this->httpRequest($url, $data, Constants::REQ_JSON, $header);
        $result = $result['choices'][0]['message']['content'] ?? false;
        return $result;
    }
    // ######################################  火山引擎 END  ######################################

    // ###################################### DeepSeek | 深度求索 START ######################################
    /**
     * 运行
     * https://www.deepseek.com/
     * @param String $command 指令内容
     * @param Int $type 指令类型
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
            'Authorization' => "Bearer {$this->api_key_ds}"
        ];
        $result = $this->httpRequest($url, $data, Constants::REQ_JSON, $header);
        $result = $result['choices'][0]['message']['content'] ?? false;
        return $result;
    }
    // ######################################  DeepSeek | 深度求索 END  ######################################

}
