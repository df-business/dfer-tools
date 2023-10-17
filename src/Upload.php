<?php
namespace Dfer\Tools;


/**
 * +----------------------------------------------------------------------
 * | 文件上传
 * | 基于tp
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
class Upload
{
 
    /**
     * 自动初始化
     */
    public function __construct()
    {
    }
    
    /**
     * 表单文件
     */
    public function formUpload()
    {
        $files=request()->file();
        // dump($files);
        if (!empty($files)) {
            try {
                $validate = new \app\validate\upload;
                $result = $validate->check($files);
                if (!$result) {
                    echo $validate->getError();
                }
                //           validate(['image'=>$vali_param])
                //                   ->check($files);
                                
                $files=$files['image'];
                // 多文件上传
                if (\is_array($files)) {
                    $savename = [];
                    foreach ($files as $file) {
                        $savename[] = \think\facade\Filesystem::putFile('e', $file);
                    }
                }
                // 单文件
                else {
                    // 上传到本地服务器
                    $savename = \think\facade\Filesystem::putFile('e', $file);
                }
                // dump($savename);
                return $savename;
            } catch (\think\exception\ValidateException $e) {
                dump($e);
                return $e;
            }
        }
    }
}
