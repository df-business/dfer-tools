<?php
namespace Dfer\Tools;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\Env;

/**
 * +----------------------------------------------------------------------
 * | 七牛云服务
 * | composer require qiniu/php-sdk
 * +----------------------------------------------------------------------
 *                      .::::.
 *                    .::::::::.            | AUTHOR: dfer
 *                    :::::::::::           | EMAIL: df_business@qq.com
 *                 ..:::::::::::'           | QQ: 3504725309
 *             '::::::::::::'
 *                .::::::::::
 *           '::::::::::::::..
 *                ..::::::::::::.
 *              ``::::::::::::::::
 *               ::::``:::::::::'        .:::.
 *              ::::'   ':::::'       .::::::::.
 *            .::::'      ::::     .:::::::'::::.
 *           .:::'       :::::  .:::::::::' ':::::.
 *          .::'        :::::.:::::::::'      ':::::.
 *         .::'         ::::::::::::::'         ``::::.
 *     ...:::           ::::::::::::'              ``::.
 *   ```` ':.          ':::::::::'                  ::::..
 *                      '.:::::'                    ':'````..
 * +----------------------------------------------------------------------
 *
 */
class QiNiuService
{
    private static $_instance = null;
    private static $_accessKey;
    private static $_secret;
    private static $_bucket;


    /**
     * @return QINIUService|null
     */
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
     * @param string $file
     * @param string $newName
     * @return mixed
     * @throws \Exception
     */
    public function upload(string $file, string $newName)
    {
        // 获取token
        $token = $this->_getToken();
        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $error) = $uploadMgr->putFile($token, $newName, $file);
        /* @var $error Error */
        if ($error !== null) {
            $this->error = $error->message();
            //日志
            $filePath = ROOT_PATH.'public/qiniu/'.date('Ymd').'/';
            if (!is_dir($filePath)) {
                mkdir($filePath, 0777, true);
            }
            $filename = $filePath."up.txt";
            file_put_contents($filename, $this->error, FILE_APPEND);
            return ['status'=>1,"url"=>""];
        }
        return ['status'=>0,"url"=>$ret['key']];
    }

    /**
     * 获取token
     * @return string
     */
    private function _getToken(): string
    {
        $auth = new Auth(self::$_accessKey, self::$_secret);
        return $auth->uploadToken(self::$_bucket);
    }
    
    /**
     * 七牛云上传文件
     *
     * $fileObj = $this->request->file('file');
     */
    public function uploadFile($fileObj)
    {
        $result=[
          'code' => 0,
          'msg'  => "",
          'data' => [],
          'url'  => "",
          'wait' => 3,
        ];
        if ($fileObj) {
            $filePath = $fileObj->getRealPath();
            $oriName = $fileObj->getInfo('name');
            $name = 'cmbbs_'.md5(microtime()) . "." . explode('.', $oriName)[1];
            $res = self::getInstance()->upload($filePath, $name);
            if ($res['status'] == 0) {
                $result['code']=1;
                $result['msg']="上传成功";
                $result['data']=['url'=>Env::get('qiniu.url').$res['url']];
            }
        }else{
         $result['msg']="上传失败";
        }
        return $result;
    }
}
