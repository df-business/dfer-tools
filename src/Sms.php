<?php

/**
 * +----------------------------------------------------------------------
 * | 短信类
 * | 支持目前市面多家服务商
 * | composer require overtrue/easy-sms
 * |
 * | 例如：
 * |       $config=config('easysms');
 * |       $config['debug']=true;
 * |       $data= [
 * |           'consignee' => $msg
 * |       ];
 * |       Sms::setConfig($config)->send($mobile,$data);
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

use Exception, Error, Closure;
use Dfer\Tools\Constants;
use Overtrue\EasySms\EasySms;

class Sms extends Common
{
    private $debug = false;
    private $smsInstance;
    private $mobile = "";
    private $gateways = ["aliyun"];
    private $config = [
        // HTTP 请求的超时时间（秒）
        'timeout' => 9.0,

        // 默认发送配置
        'default' => [
            // 网关调用策略，默认：顺序调用
            'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

            // 默认可用的发送网关
            'gateways' => [
                // 阿里云
                'aliyun',
                // 腾讯云
                'qcloud',
                // 短信宝
                'smsbao',
                // 聚合短信
                'juhe',
            ],
        ],
        // 可用的网关配置
        'gateways' => [
            'errorlog' => [
                'file' => '/tmp/easy-sms.log',
            ],
            'aliyun' => [
                'access_key_id' => '',
                'access_key_secret' => '',
                'sign_name' => '',
                'templates' => [
                    'login' => ''
                ]
            ],
            'qcloud' => [
                'sdk_app_id' => '',
                'app_key' => '',
                'sign_name' => '',
                'templates' => [
                    'login' => ''
                ]
            ],
            'smsbao' => [
                'user' => '',
                'password' => '',
                'templates' => [
                    'login' => ''
                ]
            ],
            'juhe' => [
                'app_key' => '',
                'templates' => [
                    'login' => ''
                ]
            ]
        ],
    ];


    ////////////////////////////////////////////////// 初始化 START //////////////////////////////////////////////////

    public function __construct($config = [])
    {
        if ($config)
            $this->setConfig($config);
    }

    /**
     * 设置默认参数
     * @param Array $config
     */
    public function setConfig($config)
    {
        $this->debug = $config['debug'] ?? $this->debug;
        $this->config = array_merge($this->config, $config);
        $this->smsInstance = new EasySms($this->config);
        return $this;
    }

    //////////////////////////////////////////////////  初始化 END  //////////////////////////////////////////////////

    /**
     * 发送
     * @param {Object} $mobile
     * @param {Object} $data 模板参数
     * @param {Object} $template_key 模板名
     * @param {Object} $gateways 修改默认可用的发送网关
     */
    public function send($mobile, $data, $template_key, $gateways = [])
    {
        $template_key_list = explode('.', $template_key);
        if (count($template_key_list) < 2) {
            $template_key_list[1] = $template_key_list[0];
            $template_key_list[0] = null;
        }
        $gateway_name = $template_key_list[0] ?? 'aliyun';
        $template_name = $template_key_list[1] ?? 'login';
        $params = [
            'template' => $this->config['gateways'][$gateway_name]['templates'][$template_name],
            'data' => $data
        ];
        $result = $this->smsInstance->send($mobile, $params, $gateways);
        $this->debugSms($result);
        return $result;
    }

    /**
     * 重写父级方法
     */
    public function debugSms()
    {
        if ($this->debug)
            parent::debugSms(func_get_args());
    }
}
