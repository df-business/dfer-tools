# php工具包

### 简介
包含了很多常用的方法

### 发布
- [package地址](https://packagist.org/packages/dfer/test)

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
$common = new Dfer\Tools\Common;
echo $common->about();
```

```
php index.php
```



**安装路径**
```
/vendor/dfer/tools/
```