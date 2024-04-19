# dompdf自定义字体


## 使用

**更新统计数据**
```
php load_font.php simhei simhei.ttf
```

**vendor/dompdf/dompdf/lib/fonts/installed-fonts.json**
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