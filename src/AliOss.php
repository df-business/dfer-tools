<?php

namespace Dfer\Tools;

use OSS\OssClient;
use OSS\Core\OssException;

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
 *                 ......    .!%$! ..        | AUTHOR: dfer
 *         ......        .;o*%*!  .          | EMAIL: df_business@qq.com
 *                .:;;o&***o;.   .           | QQ: 3504725309
 *        .;;!o&****&&o;:.    ..
 * +----------------------------------------------------------------------
 *
 */
class AliOss extends Common
{
	// ali访问凭证
	// https://ram.console.aliyun.com/manage/ak
	private $access_id = '';
	private $access_key = '';
	private $endpoint = 'http://oss-cn-chengdu.aliyuncs.com';
	private $bucket = 'chanpinfabu';
	private $ossClient = NULL;

	// 域名 eg:http://res.tye3.com/
	private $host = '';
	// 回调地址 eg:https://ktp.tye3.com/callback/Oss/ossUploadCallback
	private $callback_url = '';
	// oss目录 eg:ktp_tye3
	private $dir = '';
	// 调试模式
	private $debug = false;

	private function ossClient()
	{
		if (!class_exists('OSS\OssClient')) {
			$this->debug("缺少`OSS`组件");
		}
		if (is_null($this->ossClient)) {
			$this->ossClient = new OssClient($this->access_id, $this->access_key, $this->endpoint);
		}
		return $this->ossClient;
	}


	public function __construct($config = [])
	{
		$this->access_id = $config['access_id'] ?? $this->access_id;
		$this->access_key = $config['access_key'] ?? $this->access_key;
		$this->endpoint = $config['endpoint'] ?? $this->endpoint;
		$this->bucket = $config['bucket'] ?? $this->bucket;

		$this->host = $config['host'] ?? $this->host;
		$this->callback_url = $config['callback_url'] ?? $this->callback_url;
		$this->dir = $config['dir'] ?? $this->dir;
		$this->dir = $this->dir.DIRECTORY_SEPARATOR.$this->getTime(null,"Y").DIRECTORY_SEPARATOR;

		$this->debug = $config['debug'] ?? $this->debug;
	}

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

		//设置该policy超时时间
		$expire = 30;
		$end = time() + $expire;
		$expiration = $this->gmtIso8601($end);

		//最大文件大小.用户可以自己设置
		$condition = array(0 => 'content-length-range', 1 => 0, 2 => 1048576000);
		$conditions[] = $condition;
		// 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
		$start = array(0 => 'starts-with', 1 => '$key', 2 => $dir);
		$conditions[] = $start;
		$arr = array('expiration' => $expiration, 'conditions' => $conditions);

		$policy = base64_encode(json_encode($arr));
		$signature = base64_encode(hash_hmac('sha1', $policy, $this->access_key, true));

		$callback = base64_encode(json_encode($callback_param));

		$this->debug($arr, $policy);

		$response = array();
		$response['OSSAccessKeyId'] = $this->access_id;
		$response['callback'] = $callback;
		$response['dir'] = $dir;
		$response['expire'] = $end;
		$response['host'] = 'https://chanpinfabu.oss-cn-chengdu.aliyuncs.com';
		$response['policy'] = $policy;
		$response['signature'] = $signature;
		$this->showJsonBase($response);
	}


	/**
	 * 上传成功之后的回调
	 * @param {Object} $callback_function	回调函数 eg:function($data){return $this->callback($data);}
	 * @param {Object} $this->host
	 */
	public function uploadCallback($callback_function = null)
	{
		try {
			$authorizationBase64 = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
			$pubKeyUrlBase64 = $_SERVER['HTTP_X_OSS_PUB_KEY_URL'] ?? null;
			if (empty($authorizationBase64) || empty($pubKeyUrlBase64)) {
				$this->setHttpStatus(self::FORBIDDEN);
			}
			// 获取OSS的签名
			$authorization = base64_decode($authorizationBase64);
			// 获取公钥地址
			$pubKeyUrl = base64_decode($pubKeyUrlBase64);

			$pubKey = $this->httpRequest($pubKeyUrl);
			$this->debug($pubKey);
			if (empty($pubKey)) {
				$this->setHttpStatus(self::FORBIDDEN);
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
			$this->debug($status);
			if ($status == 1) {
				// 将查询字符串中的参数解析为变量			
				// parse_str($body,$post_arr);
				$post_arr = $this->getPara($body);
				$post_arr['host'] =  $this->host;
				$post_arr['fileName'] =  pathinfo($post_arr['filePath'], PATHINFO_BASENAME);
				$this->processSave($post_arr);
				$this->debug($body, urldecode($body), $post_arr);
				$this->returnData($post_arr, $callback_function);
			} else {
				$this->setHttpStatus(self::FORBIDDEN);
			}
		} catch (OssException $exception) {
			$err_msg = $this->getException($exception);
			$post_arr = ['type' => 'error', "msg" => $err_msg];
			$this->returnData($post_arr, $callback_function);
		} catch (Exception $exception) {
			$err_msg = $this->getException($exception);
			$post_arr = ['type' => 'error', "msg" => $err_msg];
			$this->returnData($post_arr, $callback_function);
		}
	}
	
	/**
	 * 图片处理,保存为新的图
	 * 设置图片尺寸（大、中、小），添加水印
	 * 
	 * @param {Object} $file_src  
	 * @param {Object} $process_list	
	 * @param {Object} $this->bucket
	 */
	private function processSave(&$post_arr)
	{
		$this->debug($post_arr);
		// 文件类型。text、image、video、application
		$mime_type = $this->getMimeTypePrefix($post_arr['mimeType']);
		// 文件在oss中的路径	
		$file_src = $post_arr['filePath'];
		
		// /path/to
		$dirname =  pathinfo($file_src, PATHINFO_DIRNAME);
		// file.txt
		$basename =  pathinfo($file_src, PATHINFO_BASENAME);
		// txt
		$extension =  pathinfo($file_src, PATHINFO_EXTENSION);
		
		// 转移上传的文件到指定目录
		$file_src_new =  $dirname.DIRECTORY_SEPARATOR.$mime_type.DIRECTORY_SEPARATOR.$basename;
		$process = $this->str("sys/saveas,o_%s,b_%s",[$this->base64UrlEncode($file_src_new),$this->base64UrlEncode($this->bucket)]);
		$this->debug($process);
		$result[] = $this->ossClient()->processObject($this->bucket, $file_src, $process);
		// 删除原始文件
		$this->delFileOss($file_src);
		// 修改原始路径
		$post_arr['filePath']=$file_src=$file_src_new;
		
		// 对文件进行加工和转存
		// 处理列表 eg:['ktp_img_l'=>null,'ktp_img_m'=>"m"]
		$process_list = $post_arr['process_list'] ?? [];	
		if (empty($process_list)) {
			$result = [];
		} else {			
			foreach ($process_list as $key => $value) {
				// 新文件名
				if (empty($value)) {
					// 覆盖原文件
					$file_src_new =  $file_src;
				} else {		
					// 保存到新路径
					$file_src_new =  $dirname.DIRECTORY_SEPARATOR.$mime_type.DIRECTORY_SEPARATOR.$value.DIRECTORY_SEPARATOR.$basename;
				}
	
				$style = "style/{$key}";
				// 通过添加另存为参数（sys/saveas）的方式将阿里云SDK处理后的文件保存至指定Bucket
				// https://help.aliyun.com/zh/oss/user-guide/sys-or-saveas?spm=5176.28426678.J_HeJR_wZokYt378dwP-lLl.19.211c5181AjnjwZ&scm=20140722.S_help@@%E6%96%87%E6%A1%A3@@2326694.S_BB1@bl+RQW@ag0+BB2@ag0+os0.ID_2326694-RL_sys/saveas-LOC_search~UND~helpdoc~UND~item-OR_ser-V_3-P0_3
				$process = $style .
					'|sys/saveas' .
					',o_' . $this->base64UrlEncode($file_src_new) .
					',b_' . $this->base64UrlEncode($this->bucket);
				$this->debug($file_src_new,$process);
				$result[] = $this->ossClient()->processObject($this->bucket, $file_src, $process);
			}
		}
	
		return $result;
	}

	/**
	 * 返回数据
	 * @param {Object} $post_arr 
	 * @param {Object} $callback_function	回调函数
	 * @return {Json} 组件数据
	 */
	private function returnData($post_arr, $callback_function)
	{
		$type = $post_arr['type'];

		if ($callback_function === null) {
			$callback_function = function ($post_arr) {
				$return = [
					'url' => $post_arr['host'] . $post_arr['filePath'],
					'title' => $post_arr['fileName']
				];
				return $return;
			};
		}

		switch ($type) {
			case 'webuploader':
				$return = [
					'code' => 1,
					'msg' => '上传成功!',
					'data' => [
						'filepath' => $post_arr['host'] . $post_arr['filePath'],
						'name' => $post_arr['fileName'],
						'id' => 0,
						'preview_url' => $post_arr['host'] . $post_arr['filePath'],
						'url' => $post_arr['host'] . $post_arr['filePath']
					],
					'url' => '',
					'wait' => 3
				];
				break;
			case 'ueditor':
				$return = [
					'state' => 'SUCCESS',
					'url' => $post_arr['host'] . $post_arr['filePath'],
					'title' => $post_arr['fileName'],
					'original' => $post_arr['fileName']
				];
				break;
			case 'error':
			default:
				$return = $post_arr;
				break;
		}

		try {
			// 调用回调函数
			$callback_function($post_arr);
		} catch (\Exception $exception) {
			$err_msg = $exception->getMessage();
			$this->debug($err_msg);
		}

		$this->debug($return);
		$this->showJsonBase($return);
	}

	/**
	 * 通过路径上传文件到oss
	 * @param {Object} $filePath 服务器上的文件路径
	 * @param {Object} $saveDir oss保存目录
	 */
	public function uploadFileOss($filePath, $saveDir)
	{
		$doesObjectExist = $this->ossClient()->doesObjectExist($this->bucket, $saveDir);
		if (!$doesObjectExist) {
			//创建虚拟目录
			$this->ossClient()->createObjectDir($this->bucket, $saveDir);
		}
		$save_file_name =  pathinfo($filePath, PATHINFO_BASENAME);
		$this->ossClient()->uploadFile($this->bucket, $saveDir . DIRECTORY_SEPARATOR . $save_file_name, $filePath);
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
		//判断object是否存在
		$doesExist = $this->ossClient()->doesObjectExist($this->bucket, $src);
		if ($doesExist) {
			//删除object
			$result = $this->ossClient()->deleteObject($this->bucket, $src);
		}
	}

	/**
	 * 重写父级方法
	 */
	public function debug()
	{
		if ($this->debug)
			parent::debug(func_get_args());
	}
}
