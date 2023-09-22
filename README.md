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
- 大部分功能都是基于tp


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

**普通用法**
```
$spService=new Office;

$title=\sprintf('订单-%s', date("Ymd", time()));
$header = ['姓名',	'电话',	'地址',	'随机数字（两位）'	,'省份（简称）',	'市（不要带市）',	'区县（不要带区县）'	,'随机数字（三位）'];
$data=Db::query("SELECT * FROM dd_shop_paybill GROUP BY receive_id");
$file_src = $spService->setTableTitle($title)
->setStyle()
->setContent($header, $data)
->saveFile($title.'.xlsx');

$file_stream = $spService->setTableTitle('2021销售记录')
->setStyle()
->setVContent($header, $data)
->saveStream('2021销售记录.xlsx');
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
php think dfer_console_create -h
<!-- 创建一个脚本 -->
php think dfer_console_create Test
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
           'dfer_console_create' => 'Dfer\Tools\Console\Create'
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


