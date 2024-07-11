# dompdf自定义字体

[官网](https://github.com/dompdf/dompdf/)

## 使用

**安装系统字体到dompdf**
```
php load_font.php system_fonts simhei
```
- 安装`C:\Windows\Fonts`里的字体到`dompdf`
- 生成`simhei`至`/vendor/dompdf/dompdf/lib/fonts/`，使`dompdf`能够调用该字体



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
- 脚本会自动替换


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
