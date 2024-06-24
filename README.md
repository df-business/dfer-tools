# php工具包

### 简介
包含了很多常用的方法

### 发布
- [package地址](https://packagist.org/packages/dfer/tools)



### 测试
```
php test.php
```
### 使用

```
composer require dfer/tools
composer require dfer/tools:*

composer update dfer/tools

composer remove dfer/tools
```

**index.php**
```
<?php
require "./vendor/autoload.php";
$common = new \Dfer\Tools\Common;
echo $common->about();
```

```
php index.php
```



**安装路径**
```
/vendor/dfer/tools/
```



## 基础环境
```
composer require topthink/framework
```
- 部分功能是基于tp


## Common
**case 1**
```
use Dfer\Tools\Common;
```
```
$dfer_common=new Common;
$dfer_common->print('test');
```

**case 2**
```
protected $common;
public function _initialize()
{
    parent::_initialize();
    $this->common =new \Dfer\Tools\Common();
}
```

## TpCommon
**case 1**
```
use Dfer\Tools\TpCommon;
```
```
$c=new TpCommon;
$name_arr=$c->getColName("cat_publish_info_comment");
```


## Address
```
use Dfer\Tools\Address;
```
```
$dfer_address=new Address;
$a=$dfer_address->provinceAbbreviation('北京');
$b=$dfer_address->getChinaChar(rand(2, 3));
```


## Office
```
composer require phpoffice/phpspreadsheet
```
```
use Dfer\Tools\Office;
```

**基本用法**
```
$spService=new Office;

// 下载
$title=\sprintf('订单-%s', date("Ymd", time()));
$header = ['姓名',    '电话',    '地址',    '随机数字（两位）'    ,'省份（简称）',    '市（不要带市）',    '区县（不要带区县）'    ,'随机数字（三位）'];
$data=Db::query("SELECT * FROM dd_shop_paybill GROUP BY receive_id");
$file_src = $spService->setTableTitle($title)
->setStyle()
->setContent($header, $data)
->saveFile($title.'.xlsx');

$file_stream = $spService->setTableTitle('2021销售记录')
->setStyle()
->setVContent($header, $data)
->saveStream('2021销售记录.xlsx');


$title = \sprintf('模板-%s', date("Ymd", time()));
$common_item=['序号','组织机构代码','企业详细名称'];
$item=array_merge($common_item,['乡镇', '所属产业','本月;工业总产值;万元','1-本月;工业总产值;万元','上年同期;本月;工业总产值;万元','上年同期;1-本月;工业总产值;万元','行业大类','1-本月;行业增加值增速;%','是否为重点监测企业']);
$lists = YjComQiyeBaseInfosReportModel::where(['year' => 2023, 'month' => 9])->order('xuhao asc')->field("xuhao,qiye_dm,qiye_mc,xiangzhen_mc,chanye,hangye_mc,s_zhongdian")->select()->toArray();
$lists_type_1=[];
foreach($lists as $key=>$v){
    $v['s_zhongdian']=$v['s_zhongdian']==2 ? '是' : '';
    $lists_type_1[]=[$v['xuhao'],$v['qiye_dm'],$v['qiye_mc'],$v['xiangzhen_mc'],$v['chanye'],null,null,null,null,$v['hangye_mc'],null,$v['s_zhongdian']];
}
$spService->setStyle()->setWidthAndHeight(35)
->setTitle($title,true)->setContent($item, $lists_type_1,[10,20,50,15,20,null,null,null,null,50,null,25])->getFile($title . '.xlsx',0);




// 上传
$files = request()->file('file_data');
$savename = \think\facade\Filesystem::disk('public')->putFile('filexls', $files);
$spService = new Office;
$items=['xuhao','qiye_dm','qiye_mc','xiangzhen_mc','chanye','chanzhi','chanzhi_lj','chanzhi_tong','chanzhi_lj_tong','hangye_mc','hangye_zeng','s_zhongdian'];
$xls_data=$spService->readFile('./storage/' . $savename,$items,2);
foreach($xls_data as $key=>&$v){
}
```




**多个栏目**
```
$db=Db::connect('db_cat_factory_dfer');

// -- 当天激活人数
$data1=$db->query("select * from cat_ems where DATE_FORMAT(FROM_UNIXTIME(createtime),'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d') GROUP BY email;");
// -- 当天登录人数
$data2=$db->query("select * from cat_user where DATE_FORMAT(FROM_UNIXTIME(logintime),'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d');");
// -- 每日社区行为数（动态、点赞数、评论数）
// -- 动态数
$data3=$db->query("select * from cat_publish_info where DATE_FORMAT(FROM_UNIXTIME(publishtime),'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d');");
// -- 点赞数
$data4=$db->query("select * from cat_like where DATE_FORMAT(FROM_UNIXTIME(create_time),'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d');");
// -- 评论数
$data5=$db->query("select * from cat_publish_info_comment where DATE_FORMAT(FROM_UNIXTIME(comment_time),'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d');");


$spService=new Office;
$title=\sprintf('统计-%s', date("Ymd", time()));
$file_src = $spService->setStyle()
->setTitle('当天激活人数')->setContent($this->getColName("cat_ems"), $data1,[50,30,20])
->setTitle('当天登录人数')->setContent($this->getColName("cat_user"), $data2)
->setTitle('当天动态数')->setContent($this->getColName("cat_publish_info"), $data2)
->setTitle('当天点赞数')->setContent($this->getColName("cat_like"), $data2)
->setTitle('当天评论数')->setContent($this->getColName("cat_publish_info_comment"), $data2)
->saveFile($title.'.xlsx');
header("Location:/".$file_src);
```


## Img
```
use Dfer\Tools\Img\Common;
use Dfer\Tools\Img\Compress;
```
```
$newname="1.jpg";
$percent = 1;  #原图压缩，不缩放，但体积大大降低
$imgcompress=new Compress($newname, $percent);
$image = $imgcompress->compressImg($newname);

$img_common=new Common;
#将临时文件转变尺寸之后移动到网站目录
$img_common->resizeJpg("1.jpg", "2.jpg", 150, 100);

```


## Console
> 自动生成控制台脚本


**支持类型**
- workerman脚本
- 普通控制台脚本
- php加密、解密脚本

```
<!-- 帮助 -->
php think dfer:console_create -h
<!-- 创建一个脚本 -->
php think dfer:console_create Test
```



**config/console.php**
```
<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
// 自定义指令
return [
    // 指令定义
    'commands' => [
           'dfer:console_create' => 'Dfer\Tools\TpConsole\Create'
    ]
];

```
- 自动生成脚本到`app\command\`
- 自动添加指令到`console.php`


**application/command.php**
```
<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'Dfer\Tools\Console\Create'
];


```



**开启文件监控组件**
```
const DEBUG=true;
```
- 不支持windows

**多线程**
- 不支持windows。在windows下只支持单线程



## DingTalk
```
use Dfer\Tools\DingTalk;
```

```
/**
 * 钉钉登录
 *
 * @ApiMethod (POST)
 * @param string $authCode  授权码
 */
public function dd_login()
{
    $authCode=isset($_POST['authCode'])?$_POST['authCode']:"";
    $service=new DingTalk();
    $accessToken=$service->getUserAccessToken($authCode);
    $users=$service->getContactUsers($accessToken);
    $this->common->log([$_GET,$_POST,$accessToken,$users]);

    $account='jiangxiao@codemao.cn';
    $ret = $this->auth->dd_login($account);
    if ($ret) {
        $data = ['userinfo' => $this->auth->getUserinfo()];
        $this->success(__('Logged in successful'), $data);
    } else {
        $this->error($this->auth->getError());
    }
}
```


## QiNiuService
```
composer require qiniu/php-sdk
```

```
/**
 * 七牛云上传
 **/
public function uploadQN()
{
 $fileObj = $this->request->file('file');
 $result=\Dfer\Tools\QiNiuService::getInstance()->uploadFile($fileObj);

 if($result['code']==0){
  $this->error('缺少参数[file]',$result);
 }
 else{
  $this->success('',$result);
 }
}
```




## QrCode
```
composer require endroid/qr-code
```

```
use Endroid\QrCode\Color\Color;
```

```
public function index()
{

    $qr=new \Dfer\Tools\QrCode;
    $data=$qr->setStyle(500,30,new Color(255, 250, 232))
    ->setText("二维码测试",new Color(0, 0, 0))
    ->setData('http://www.baidu.com/')
    ->setLogo()
    ->getFile();
    $this->success('请求成功!', $data);
}
```



## Mail

```
public function index()
{
    $mail=new \Dfer\Tools\Mail;
    $data=$mail->instance(['debug'=>true])
    ->send('test@qq.com','邮件主题','邮件内容');
    $this->success('请求成功!', $data);
}
```



## AliOss
```
composer require aliyuncs/oss-sdk-php
```

**/public/static/js/ueditor/custom/dialogs/image/image.js**
```
// actionUrl = editor.getActionUrl(editor.getOpt('imageActionName'))
var actionUrl = 'https://chanpinfabu.oss-cn-chengdu.aliyuncs.com';
uploader = _this.uploader = WebUploader.create({
    server: actionUrl
});

uploader.on('uploadBeforeSend', function (file, data, header) {
    //这里可以通过data对象添加POST参数
    header['X_Requested_With'] = 'XMLHttpRequest';
    $.ajax({
        type:"post",
        url:"/user/asset/getRequestParams/type/ueditor",
        data:{process_list:{'ktp_img_ueditor_m':null}},
        success:function (res) {
            if(res){
                try{
                    console.log('getRequestParams',res);
                    $.extend(data,{
                        'key':res.dir + df_tools_common.generateRandomFileName(data.name),
                        'policy':res.policy,
                        'OSSAccessKeyId':res.OSSAccessKeyId,
                        'success_action_status':'200',//让服务端返回200,不然默认会返回204
                        'callback':res.callback,
                        'signature':res.signature
                    });
                }catch(e){
                    console.error(e);
                }
            }else{
                console.log('出错');
            }
        },
        error : function(XMLHttpRequest, textStatus, errorThrown) {
            alert("ajax error");
        },
        complete : function(XMLHttpRequest,status){ //请求完成后最终执行参数
            if(status == 'timeout'){
                alert('请求超时，请稍后再试！');
            }
        },
        async : false
    });
    console.log("data",data)
    header['Access-Control-Allow-Origin'] = "*";
});
```

**/public/themes/admin_v1/user/webuploader.html**
```
uploader = WebUploader.create({
    server: "https://chanpinfabu.oss-cn-chengdu.aliyuncs.com",
});
uploader.on('uploadBeforeSend', function (file, data, header) {
    //这里可以通过data对象添加POST参数
    header['X_Requested_With'] = 'XMLHttpRequest';
    $.ajax({
        type:"post",
        url:"/user/asset/getRequestParams/type/webuploader",
        // data:{process_list:{'ktp_img_l':null,'ktp_img_m':"m",'ktp_img_s':"s"}},
        data:{process_list:{'ktp_img_1200':null,'ktp_img_600':"600",'ktp_img_200':"280"}},
        success:function (res) {
            if(res){
                try{
                    console.log('getRequestParams',res);
                    $.extend(data,{
                        'key':res.dir + df_tools_common.generateRandomFileName(data.name),
                        'policy':res.policy,
                        'OSSAccessKeyId':res.OSSAccessKeyId,
                        'success_action_status':'200',//让服务端返回200,不然默认会返回204
                        'callback':res.callback,
                        'signature':res.signature
                    });
                }catch(e){
                    console.error(e);
                }
            }else{
                console.log('出错');
            }
        },
        error : function(XMLHttpRequest, textStatus, errorThrown) {
            alert("ajax error");
        },
        complete : function(XMLHttpRequest,status){ //请求完成后最终执行参数
            if(status == 'timeout'){
                alert('请求超时，请稍后再试！');
            }
        },
        async : false
    });
    console.log("data",data)
    header['Access-Control-Allow-Origin'] = "*";
});

```

**/app/api/controller/Oss.php**
```
namespace app\api\controller;

use Dfer\Tools\Statics\{Common};
use Dfer\Tools\AliOss;
use Exception;

use app\api\model\OssUploadRecordModel;

class Oss extends Base
{
    private $userId=0;

    public function __construct()
    {
        parent::__construct(app());

        if (!class_exists('Dfer\Tools\AliOss')) {
            die("缺少`dfer/tools`组件");
        }
    }

    public function getRequestParams(){
        // 组件类型
        $type = $this->request->param('type','ueditor');
        // 资源加工列表
        $process_list = $this->request->param('process_list',[]);

        $access_id =  config('oss.access_id');
        $access_key =  config('oss.access_key');
        $callback_url =  config('oss.callback_url');

        $dir =  config('oss.dir');
        $user_id=$this->userId;
        $debug=1;
        Common::debug(compact('access_id','access_key','dir','callback_url','debug'),false);
        $oss=new AliOss(compact('access_id','access_key','dir','callback_url','debug'),false);
        $oss->getRequestParams(compact('user_id','type','process_list'));
    }


    public function uploadCallback($var = null)
    {
        $access_id =  config('oss.access_id');
        $access_key =  config('oss.access_key');
        $bucket =  config('oss.bucket');
        $endpoint =  config('oss.endpoint');
        $host =  config('oss.host');
        // 调试日志保存在`/data/logs/`
        $debug=1;
        Common::debug(compact('access_id','access_key','bucket','host','debug','endpoint'));
        $oss=new AliOss(compact('access_id','access_key','bucket','endpoint','host','debug'));
        $oss->uploadCallback(function($status,$data){return $this->callback($status,$data);});
    }

    public function callback($status,$post_arr = null)
    {
        if(intval($status)){
            OssUploadRecordModel::create([
                'user_id'=>$post_arr['user_id'],
                'file_path'=>$post_arr['filePath'],
                'url'=>$post_arr['host'].$post_arr['filePath']
            ]);
        }



        $return = [
            'code' => $status ? 1 : 0,
            'msg' => $status ? '上传成功!' : '上传失败!',
            'original' => $post_arr['fileName'],
            'state' => $status ? 'SUCCESS' : 'FAIL',
            'title' => $post_arr['fileName'],
            'url' => $post_arr['host'] . $post_arr['filePath']
        ];

        if (!$status) {
            $return['error'] = $post_arr['error'];
        }

        Common::showJsonBase($return);
    }
}
```

**/data/config/oss.php**
```
<?php
return [
    'access_id'=>'*************',
    'access_key'=>'*************',
    'callback_url'=>'https://www.dfer.site/api/oss/uploadCallback',
    'dir'=>'www_dfer_site',
    'host'=>'http://oss.dfer.site/',
    'bucket'=>'df-linux-oss',
    'endpoint'=>'oss-cn-hangzhou.aliyuncs.com',
];
```




---
©2022-2024 Dfer.Site
