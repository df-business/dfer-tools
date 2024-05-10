# dompdf自定义字体

[官网](https://github.com/dompdf/dompdf/)

## 使用

**生成字体文件到dompdf**
```
php load_font.php simhei simhei.ttf
```
- 生成`simhei`至`/vendor/dompdf/dompdf/lib/fonts/`，使pdf能够调用该字体



**vendor/dompdf/dompdf/lib/fonts/installed-fonts.json**
```
{
    "simhei": {
        "normal": "F:\\Users\\dfer\\Documents\\dfer\\Project\\yj.tye3.com\\vendor\\dompdf\\dompdf\/lib\/fonts\/simhei",
        "bold": "F:\\Users\\dfer\\Documents\\dfer\\Project\\yj.tye3.com\\vendor\\dompdf\\dompdf\/lib\/fonts\/simhei",
        "italic": "F:\\Users\\dfer\\Documents\\dfer\\Project\\yj.tye3.com\\vendor\\dompdf\\dompdf\/lib\/fonts\/simhei",
        "bold_italic": "F:\\Users\\dfer\\Documents\\dfer\\Project\\yj.tye3.com\\vendor\\dompdf\\dompdf\/lib\/fonts\/simhei"
    }
}
```
替换为
```
{
    "simhei": {
        "normal": "simhei",
        "bold": "simhei",
        "italic": "simhei",
        "bold_italic": "simhei"
    }
}
```
- 需要手动替换此文件，否则会报错


**IndexController.php**
```
$html="你好";
$html =
<<<STR
<html lang="zh-CN">
    <head>
        <meta charset="UTF-8">        
            <style>               
               * {
                   font-family: simhei;                    
               }              
            </style>
    </head>
    <body>
        {$html}
    </body>
</html>
STR;

$dompdf = new Dompdf();
$dompdf->loadHtml($html,'UTF-8');    
$dompdf->setPaper('A4', 'portrait');    
$dompdf->render();    
$filename = 'pdf_'. date('His') .'.pdf';
$dompdf->stream($filename);
```


## html样式
>不兼容所有的css样式，需要参照文档来添加样式

[demo](https://eclecticgeek.com/dompdf/debug.php)
