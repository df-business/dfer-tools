<?php

/**
 * +----------------------------------------------------------------------
 * | 阿里云oss类
 * | composer require aliyuncs/oss-sdk-php
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

use Exception,Error,Closure;
use OSS\OssClient;
use OSS\Core\OssException;
use Dfer\Tools\Constants;

class AliOss extends Common
{
    // ali访问凭证  https://ram.console.aliyun.com/manage/ak
    private $access_id = '';
    private $access_key = '';
    // Endpoint（地域节点）
    private $endpoint = '';
    // Bucket 域名
    private $bucket = '';
    // oss客户端对象
    private $ossClient = NULL;

    // 域名 eg:http://res.tye3.com/
    private $host = '';
    // 回调地址 eg:https://ktp.tye3.com/callback/Oss/ossUploadCallback
    private $callback_url = '';
    // oss目录    eg:ktp_tye3
    // 路径中的两条斜杠会自动添加同名目录。eg：`https://res.tye3.com/kp_tye3//2024/image/tYGKNP9trMWHR9EQ.jpg`实际对应路径为`oss根目录/kp_tye3/kp_tye3/2024/image/tYGKNP9trMWHR9EQ.jpg`
    private $dir = '';
    // 调试模式
    private $debug = false;
    // 回调返回的参数
    private $post_arr = [];

    public function __construct($config = [], $needOssClient = true)
    {
        $this->access_id = $config['access_id'] ?? $this->access_id;
        $this->access_key = $config['access_key'] ?? $this->access_key;
        $this->endpoint = $config['endpoint'] ?? $this->endpoint;
        $this->bucket = $config['bucket'] ?? $this->bucket;

        $this->host = $config['host'] ?? $this->host;
        $this->callback_url = $config['callback_url'] ?? $this->callback_url;
        $this->dir = $config['dir'] ?? $this->dir;
        // 默认的上传目录
        $this->dir = $this->dir . DIRECTORY_SEPARATOR;

        $this->debug = $config['debug'] ?? $this->debug;

        if ($needOssClient) {
            $this->ossClientInit();
        }
    }

    ////////////////////////////////////////////////// 核心方法 START //////////////////////////////////////////////////

    /**
     * 向oss发起上传请求时的必要参数
     * @param {Object} $ext_param 附带的参数，会被解析为查询字符串中的参数 eg:['user_id'=123]
     * @param {Object} $this->callback_url
     * @param {Object} $this->dir
     * @param {Object} $this->access_key
     * @param {Object} $this->access_id
     */
    public function getRequestParams($ext_param = [])
    {
        $callback_url = $this->callback_url;
        $dir = $this->dir;

        // https://help.aliyun.com/zh/oss/developer-reference/callback?spm=5176.28426678.J_HeJR_wZokYt378dwP-lLl.317.6c295181xaqQxk&scm=20140722.S_help@@%E6%96%87%E6%A1%A3@@31989.S_BB1@bl+RQW@ag0+BB2@ag0+os0.ID_31989-RL_imageInfo-LOC_search~UND~helpdoc~UND~item-OR_ser-V_3-P0_11
        $callback_param = [
            'callbackUrl' => $callback_url,
            'callbackBody' => http_build_query($ext_param) . '&filePath=${object}&size=${size}&mimeType=${mimeType}&width=${imageInfo.width}&height=${imageInfo.height}',
            'callbackBodyType' => "application/x-www-form-urlencoded"
        ];
        // $this->debug($callback_param);

        //设置该policy超时时间（秒）
        $expire = 30;
        // 截止时间
        $end_time = time() + $expire;
        $expiration = $this->gmtIso8601($end_time);

        //最大文件大小.用户可以自己设置
        $condition = array(0 => 'content-length-range', 1 => 0, 2 => 1048576000);
        $conditions[] = $condition;
        // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
        $start = array(0 => 'starts-with', 1 => '$key', 2 => $dir);
        $conditions[] = $start;
        $arr = array('expiration' => $expiration, 'conditions' => $conditions);

        $policy = base64_encode(json_encode($arr));
        // HMAC具有单向性，从HMAC值反向推导出原始消息或密钥在计算上是不可行的。尽管 HMAC 本身不能被“解密”以恢复原始密钥或消息，但攻击者可能会尝试使用暴力破解或字典攻击来猜测密钥。因此，确保使用足够强大且难以猜测的密钥是非常重要的。
        // 安全性：sha256 > sha1 > md5 性能：md5 > sha1 > sha256
        // 获取`$algo`的可选值
        // var_dump(hash_hmac_algos());
        $signature = base64_encode(hash_hmac('sha1', $policy, $this->access_key, true));

        $callback = base64_encode(json_encode($callback_param));

        $response = array();
        $response['OSSAccessKeyId'] = $this->access_id;
        $response['callback'] = $callback;
        $response['dir'] = $dir;
        $response['policy'] = $policy;
        $response['signature'] = $signature;
        $this->showJsonBase($response);
    }


    /**
     * 上传成功之后的回调
     *
     * 暂不支持分片上传。参考：https://help.aliyun.com/zh/oss/user-guide/multipart-upload?spm=5176.28426678.J_HeJR_wZokYt378dwP-lLl.251.211c5181zWp9u3&scm=20140722.S_help@@%E6%96%87%E6%A1%A3@@31850.S_BB1@bl+RQW@ag0+BB2@ag0+hot+os0.ID_31850-RL_%E5%88%86%E7%89%87-LOC_search~UND~helpdoc~UND~item-OR_ser-V_3-P0_0
     *
     * @param {Object} $callback_function    回调匿名函数 eg:function($data){return $this->callback($data);}
     * @param {Object} $this->host
     */
    public function uploadCallback(Closure $callback_function = null)
    {
        try {

            $authorizationBase64 = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
            $pubKeyUrlBase64 = $_SERVER['HTTP_X_OSS_PUB_KEY_URL'] ?? null;
            if (empty($authorizationBase64) || empty($pubKeyUrlBase64)) {
                $this->setHttpStatus(Constants::FORBIDDEN);
            }
            // 获取OSS的签名
            $authorization = base64_decode($authorizationBase64);
            // 获取公钥地址
            $pubKeyUrl = base64_decode($pubKeyUrlBase64);

            $pubKey = $this->httpRequest($pubKeyUrl);
            // $this->debug($pubKey);
            if (empty($pubKey)) {
                $this->setHttpStatus(Constants::FORBIDDEN);
            }
            // 获取回调body
            $body = file_get_contents('php://input');

            // 拼接待签名字符串
            $authStr = '';
            $path = $_SERVER['REQUEST_URI'];
            $pos = strpos($path, '?');

            if ($pos === false) {
                $authStr = urldecode($path) . "\n" . $body;
            } else {
                $authStr = urldecode(substr($path, 0, $pos)) . substr($path, $pos, strlen($path) - $pos) . "\n" . $body;
            }

            // 验证签名
            $status = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);
            // $this->debug($status);
            $this->post_arr = $this->getPara($body);
            if ($status == 1) {
                // 将查询字符串中的参数解析为变量
                // parse_str($body,$this->post_arr);
                $this->post_arr['host'] = $this->host;
                $this->post_arr['fileName'] = pathinfo($this->post_arr['filePath'], PATHINFO_BASENAME);
                $this->processSave();
                $this->returnData($callback_function);
            } else {
                $this->setHttpStatus(Constants::FORBIDDEN);
            }
        } catch (OssException $exception) {
            $this->debug($exception);
            $err_msg = $exception->getMessage();
            $this->post_arr['error'] = $err_msg;
            $this->returnData($callback_function);
        } catch (Exception $exception) {
            $this->debug($exception);
            $err_msg = $exception->getMessage();
            $this->post_arr['error'] = $err_msg;
            $this->returnData($callback_function);
        } catch (Error $exception) {
            $this->debug($exception);
            $err_msg = $exception->getMessage();
            $this->post_arr['error'] = $err_msg;
            $this->returnData($callback_function);
        }
    }

    //////////////////////////////////////////////////  核心方法 END  //////////////////////////////////////////////////

    ////////////////////////////////////////////////// 自定义方法 START //////////////////////////////////////////////////

    /**
     * 初始化OssClient
     */
    private function ossClientInit()
    {
        if (!class_exists('OSS\OssClient')) {
            $this->debug("缺少`OSS`组件");
        }
        if (is_null($this->ossClient)) {
            $this->ossClient = new OssClient($this->access_id, $this->access_key, $this->endpoint);
        }
        return true;
    }

    /**
     * 图片处理,保存为新的图
     * 设置图片尺寸（大、中、小），添加水印
     *
     * @param {Object} $file_src
     * @param {Object} $process_list
     * @param {Object} $this->bucket
     */
    private function processSave()
    {
        $this->debug('processSave', $this->post_arr);
        // 文件类型。text、image、video、application
        $mime_type = $this->getMimeTypePrefix($this->post_arr['mimeType']);
        // 文件在oss中的路径
        $file_src = $this->post_arr['filePath'];

        // /path/to
        $dirname = pathinfo($file_src, PATHINFO_DIRNAME);
        // file.txt
        $basename = pathinfo($file_src, PATHINFO_BASENAME);
        // txt
        $extension = pathinfo($file_src, PATHINFO_EXTENSION);
        // file
        $filename = pathinfo($file_src, PATHINFO_FILENAME);

        // ********************** 移动文件 START **********************
        // 转移上传的文件到指定目录。根据类型、年、月设置上传目录
        $file_src_new = $dirname . DIRECTORY_SEPARATOR . $mime_type . DIRECTORY_SEPARATOR . $this->getTime(null, "Y") . DIRECTORY_SEPARATOR . $this->getTime(null, "m") . DIRECTORY_SEPARATOR . $basename;
        $result[] = $this->copyFileOss($file_src, $file_src_new);
        // 删除原始文件
        $this->delFileOss($file_src);
        // 修改原始路径
        $this->post_arr['filePath'] = $file_src = $file_src_new;
        // **********************  移动文件 END  **********************


        // 异步处理。图片处理默认使用`x-oss-process`，视频截帧默认使用`x-oss-async-process`
        $is_async = false;

        // 对文件进行加工和转存
        // 处理列表 eg:['ktp_img_l'=>null,'ktp_img_m'=>"m"]
        $process_list = $this->post_arr['process_list'] ?? [];
        if (empty($process_list)) {
            $result = [];
        } else {
            foreach ($process_list as $key => $value) {
                if (is_array($value)) {
                    if (isset($value[1]))
                        $basename = "{$filename}.{$value[1]}";
                    $value = $value[0];
                    $is_async = true;
                }
                // 新文件名
                if (empty($value)) {
                    // 覆盖原文件
                    $file_src_new = $file_src;
                } else {
                    // 保存到新路径
                    $file_src_new = $dirname . DIRECTORY_SEPARATOR . $mime_type . DIRECTORY_SEPARATOR . $this->getTime(null, "Y") . DIRECTORY_SEPARATOR . $this->getTime(null, "m") . DIRECTORY_SEPARATOR . $value . DIRECTORY_SEPARATOR . $basename;
                }
                $result[] = $this->saveFileOss($file_src, $file_src_new, $key, $is_async);
            }
        }

        return $result;
    }

    /**
     * 返回数据
     * @param {Object} $callback_function
     * @return {Json} 前端组件需要的数据
     */
    private function returnData(Closure $callback_function)
    {
        $this->debug($this->post_arr);

        $type = $this->post_arr['type'];
        // 运行状态。true 成功 false 失败
        $status = isset($this->post_arr['error']) ? false : true;

        if ($callback_function === null) {
            $callback_function = function ($post_arr) {
                return null;
            };
        }

        // 调用回调函数
        $callback_function($status,$this->post_arr);

        switch ($type) {
            case 'webuploader':
                $return = [
                    'code' => $status ? 1 : 0,
                    'msg' => $status ? '上传成功!' : '上传失败!',
                    'data' => [
                        'id' => 0,
                        'name' => $this->post_arr['fileName'],
                        'filepath' => $this->post_arr['host'] . $this->post_arr['filePath'],
                        'preview_url' => $this->post_arr['host'] . $this->post_arr['filePath'],
                        'url' => $this->post_arr['host'] . $this->post_arr['filePath'],
                        'path' => $this->post_arr['filePath']
                    ],
                    'url' => '',
                    'wait' => 3
                ];
                break;
            case 'ueditor':
                $return = [
                    'state' => $status ? 'SUCCESS' : 'FAIL',
                    'title' => $this->post_arr['fileName'],
                    'original' => $this->post_arr['fileName'],
                    'url' => $this->post_arr['host'] . $this->post_arr['filePath'],
                    'path' => $this->post_arr['filePath']
                ];
                break;
            default:
                $return = [
                    'status' => $status,
                    'title' => $this->post_arr['fileName'],
                    'url' => $this->post_arr['host'] . $this->post_arr['filePath'],
                    'path' => $this->post_arr['filePath']
                ];
                break;
        }

        if (!$status) {
            $return['error'] = $this->post_arr['error'];
        }


        $this->debug($return);
        $this->showJsonBase($return);
    }

    /**
     * 保存文件
     * https://help.aliyun.com/zh/oss/user-guide/sys-or-saveas?spm=5176.28426678.J_HeJR_wZokYt378dwP-lLl.23.211c5181zWp9u3&scm=20140722.S_help@@%E6%96%87%E6%A1%A3@@2326694.S_BB1@bl+RQW@ag0+BB2@ag0+os0.ID_2326694-RL_syssaveas-LOC_search~UND~helpdoc~UND~item-OR_ser-V_3-P0_2
     *
     * @param {Object} $from_src 源文件路径
     * @param {Object} $to_src 目标路径
     * @param {Object} $style 样式。目前仅支持样式名称调用。
     * 例子：
     * 样式名称 style/test
     * 样式参数 image/resize,m_fixed,w_100,h_100/rotate,90 地址栏调用：http://res.tye3.com/kp_tye3/2024/image/tYGKNP9trMWHR9EQ.jpg?x-oss-process=image/auto-orient,1/resize,m_pad,w_200,h_200
     * @param {Object} $is_async 异步处理。图片处理默认使用`x-oss-process`，视频截帧默认使用`x-oss-async-process`
     */
    public function saveFileOss($from_src, $to_src, $style = null, $is_async = false)
    {
        //判断object是否存在
        $doesExist = $this->ossClient->doesObjectExist($this->bucket, $from_src);
        if ($doesExist) {
            switch ($style) {
                case 'h264-mp4-360p':
                    // 调用系统样式   例：https://oss.console.aliyun.com/bucket/oss-cn-chengdu/chanpinfabu/process/new_imm/media-processing
                    $style = "video/convert,f_mp4,vcodec_h264,fps_25,fpsopt_1,s_640x,sopt_1,scaletype_fit,arotate_1,crf_25,g_250,acodec_aac,ar_44100,ac_2,ab_64000,abopt_1";
                    break;
                case 'h264-mp4-540p':
                    $style = "video/convert,f_mp4,vcodec_h264,fps_25,fpsopt_1,s_960x,sopt_1,scaletype_fit,arotate_1,crf_25,g_250,acodec_aac,ar_44100,ac_2,ab_96000,abopt_1";
                    break;
                case 'h264-mp4-720p':
                    $style = "video/convert,f_mp4,vcodec_h264,fps_25,fpsopt_1,s_1280x,sopt_1,scaletype_fit,arotate_1,crf_26,g_250,acodec_aac,ar_44100,ac_2,ab_128000,abopt_1";
                    break;
                case 'h264-mp4-1080p':
                    $style = "video/convert,f_mp4,vcodec_h264,fps_25,fpsopt_1,s_1920x,sopt_1,scaletype_fit,arotate_1,crf_26,g_250,acodec_aac,ar_44100,ac_2,ab_160000,abopt_1";
                    break;
                case 'h264-mp4-1440p':
                    $style = "video/convert,f_mp4,vcodec_h264,fps_60,fpsopt_1,s_2560x,sopt_1,scaletype_fit,arotate_1,crf_26,g_250,acodec_aac,ar_48000,ab_320000,abopt_1";
                    break;
                case 'h264-mp4-2160p':
                    $style = "video/convert,f_mp4,vcodec_h264,fps_60,fpsopt_1,s_3840x,sopt_1,scaletype_fit,arotate_1,crf_26,g_250,acodec_aac,ar_48000,ab_512000,abopt_1";
                    break;
                default:
                    $style = "style/{$style}";
                    break;
            }

            $style = "{$style}|";
            // 通过添加另存为参数（sys/saveas）的方式将阿里云SDK处理后的文件保存至指定Bucket
            // https://help.aliyun.com/zh/oss/user-guide/sys-or-saveas?spm=5176.28426678.J_HeJR_wZokYt378dwP-lLl.19.211c5181AjnjwZ&scm=20140722.S_help@@%E6%96%87%E6%A1%A3@@2326694.S_BB1@bl+RQW@ag0+BB2@ag0+os0.ID_2326694-RL_sys/saveas-LOC_search~UND~helpdoc~UND~item-OR_ser-V_3-P0_3
            $process = $this->str("{0}sys/saveas,o_{1},b_{2}", [$style, $this->base64UrlEncode($to_src), $this->base64UrlEncode($this->bucket)]);
            $this->debug($from_src, $to_src, $process, $is_async);

            if ($is_async)
                $result = $this->ossClient->asyncProcessObject($this->bucket, $from_src, $process);
            else
                $result = $this->ossClient->processObject($this->bucket, $from_src, $process);

            return $result;
        }
        return false;
    }

    /**
     * 通过路径上传文件到oss
     * @param {Object} $filePath 服务器上的文件路径
     * @param {Object} $saveDir oss保存目录
     */
    public function uploadFileOss($filePath, $saveDir)
    {
        $doesObjectExist = $this->ossClient->doesObjectExist($this->bucket, $saveDir);
        if (!$doesObjectExist) {
            //创建虚拟目录
            $this->ossClient->createObjectDir($this->bucket, $saveDir);
        }
        $save_file_name = pathinfo($filePath, PATHINFO_BASENAME);
        $this->ossClient->uploadFile($this->bucket, $saveDir . DIRECTORY_SEPARATOR . $save_file_name, $filePath);
        //删除原文件
        if (is_file($filePath)) {
            unlink($filePath);
        }
        return $save_file_name;
    }

    /**
     * 删除文件
     * @param {Object} $src 文件路径
     */
    public function delFileOss($src)
    {
        $this->debug($this->bucket, $src);
        //判断object是否存在
        $doesExist = $this->ossClient->doesObjectExist($this->bucket, $src);
        if ($doesExist) {
            //删除object
            return $this->ossClient->deleteObject($this->bucket, $src);
        }
        return false;
    }

    /**
     * 复制文件
     * https://help.aliyun.com/zh/oss/developer-reference/api-reference/?spm=a2c4g.11186623.0.0.17ac4dcamRAH1J
     * @param {Object} $from_src 源文件路径
     * @param {Object} $to_src 目标路径
     */
    public function copyFileOss($from_src, $to_src)
    {
        $this->debug($this->bucket,$this->ossClient,$from_src,$to_src);
        //判断object是否存在
        $doesExist = $this->ossClient->doesObjectExist($this->bucket, $from_src);
        if ($doesExist) {
            return $this->ossClient->copyObject($this->bucket, $from_src, $this->bucket, $to_src);
        }
        return false;
    }

    /**
     * 重写父级方法
     */
    public function debug()
    {
        if ($this->debug)
            parent::debug(func_get_args());
    }

    //////////////////////////////////////////////////  自定义方法 END  //////////////////////////////////////////////////
}
