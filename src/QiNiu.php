<?php

namespace Dfer\Tools;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

/**
 * +----------------------------------------------------------------------
 * | 七牛云类
 * | composer require qiniu/php-sdk
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
class QiNiu
{
    private static $_instance = null;
    private static $_accessKey;
    private static $_secret;
    private static $_bucket;

    public static function getInstance(): ?QINIUService
    {
        self::$_secret = Env::get("qiniu.secret");
        self::$_accessKey = Env::get("qiniu.key");
        self::$_bucket = Env::get('qiniu.bucket');
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     *
     * @param {Object} string $file	文件路径
     * @param {Object} string $newName	新文件名
     * @param {Object} string $logRoot	日志根目录	eg:ROOT_PATH
     */
    public function upload(string $file, string $newName, string $logRoot)
    {
        // 获取token
        $token = $this->getToken();
        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $error) = $uploadMgr->putFile($token, $newName, $file);

        if ($error !== null) {
            $this->error = $error->message();
            //日志
            $filePath = $logRoot . 'public/qiniu/' . date('Ymd') . '/';
            if (!is_dir($filePath)) {
                mkdir($filePath, 0777, true);
            }
            $filename = $filePath . "up.txt";
            file_put_contents($filename, $this->error, FILE_APPEND);
            return ['status' => 1, "url" => ""];
        }
        return ['status' => 0, "url" => $ret['key']];
    }

    /**
     * 获取token
     * @return string
     */
    private function getToken(): string
    {
        $auth = new Auth(self::$_accessKey, self::$_secret);
        return $auth->uploadToken(self::$_bucket);
    }


    /**
     * 七牛云上传文件
     * $fileObj = $this->request->file('file');
     * @param {Object} $fileObj
     * @param {Object} $qiniuHost 七牛云根域名 eg:Env::get('qiniu.url')
     */
    public function uploadFile($fileObj, $qiniuHost)
    {
        $result = [
            'code' => 0,
            'msg'  => "",
            'data' => [],
            'url'  => "",
            'wait' => 3,
        ];
        if ($fileObj) {
            $filePath = $fileObj->getRealPath();
            $oriName = $fileObj->getInfo('name');
            $name = 'cmbbs_' . md5(microtime()) . "." . explode('.', $oriName)[1];
            $res = self::getInstance()->upload($filePath, $name);
            if ($res['status'] == 0) {
                $result['code'] = 1;
                $result['msg'] = "上传成功";
                $result['data'] = ['url' => $qiniuHost . $res['url']];
            }
        } else {
            $result['msg'] = "上传失败";
        }
        return $result;
    }
}
