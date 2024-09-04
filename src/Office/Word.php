<?php

/**
 * +----------------------------------------------------------------------
 * | 文档类
 * | 支持odt、rtf、docx、html、pdf文件
 * | https://phpoffice.github.io/PHPWord/
 * |
 * | composer require phpoffice/phpword
 * |
 * | pdf依赖于dompdf
 * | composer require dompdf/dompdf
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
 *                 ......    .!%$! ..     | AUTHOR: dfer
 *         ......        .;o*%*!  .       | EMAIL: df_business@qq.com
 *                .:;;o&***o;.   .        | QQ: 3504725309
 *        .;;!o&****&&o;:.    ..          | WEBSITE: http://www.dfer.site
 * +----------------------------------------------------------------------
 *
 */

namespace Dfer\Tools\Office;

use PhpOffice\PhpWord\{PhpWord, IOFactory, Settings};
use PhpOffice\PhpWord\SimpleType\DocProtect;
use Dfer\Tools\{Common,Constants};

class Word extends Common
{
    protected static $wordInstance, $sectionInstance;
    protected $headerStyle = [
        'name' => '宋体',
        'size' => 22,
        'bold' => true
    ];
    protected $headerFormat = [
        'alignment' => 'center'
    ];
    protected $bodyStyle = [
        'name' => '宋体',
        'size' => 16,
    ];
    protected $bodyFormat = [
        'lineHeight' => 1.5, 'alignment' => 'both', 'indentation' => ['firstLine' => 2 * 16 * 20]
    ];
    protected $callback;

    /**
     * 静态获取PhpWord实例
     */
    private static function wordInstance()
    {
        if (is_null(self::$wordInstance)) {
            self::$wordInstance = new PhpWord();
        }
        return self::$wordInstance;
    }

    private static function sectionInstance()
    {
        if (is_null(self::$sectionInstance)) {
            self::$sectionInstance = self::wordInstance()->addSection();
        }
        return self::$sectionInstance;
    }

    /**
     * 设置基础样式
     */
    protected function initBaseStyle()
    {
        $phpWord = self::wordInstance();
        // 文件信息
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('dfer');
        $properties->setDescription('http://www.dfer.site');
        // 文件保护
        $documentProtection = $phpWord->getSettings()->getDocumentProtection();
        $documentProtection->setEditing(DocProtect::READ_ONLY);
        $documentProtection->setPassword('df');
    }

    /**
     * 设置基本样式
     * @param {Object} array $headerStyle    头部样式
     * @param {Object} array $bodyStyle    主体样式
     * @param {Object} array $headerFormat    头部格式
     * @param {Object} array $bodyFormat    主体格式
     */
    public function setStyle(array $headerStyle = [], array $bodyStyle = [], array $headerFormat = [], array $bodyFormat = [])
    {
        $this->headerStyle = array_merge($this->headerStyle, $headerStyle);
        $this->bodyStyle = array_merge($this->bodyStyle, $bodyStyle);
        $this->headerFormat = array_merge($this->headerFormat, $headerFormat);
        $this->bodyFormat = array_merge($this->bodyFormat, $bodyFormat);
        return $this;
    }

    /**
     * 设置标题
     * @param {Object} string $title 主标题
     * @param {Object} string $second_title 二级标题
     */
    public function setTitle(string $title, string $second_title = null)
    {
        $section = self::sectionInstance();
        $section->addText($title, array_merge($this->headerStyle, ['size' => Constants::S2]), $this->headerFormat);
        if ($second_title)
            $section->addText($second_title, array_merge($this->headerStyle, ['size' => Constants::S3]), $this->headerFormat);
        $section->addTextBreak(2);
        return $this;
    }

    /**
     * 设置内容
     * @param {Object} array $header    标题数据
     * @param {Object} array $data    主体数据
     * @param {Object} array $width    行宽
     * @param {Object} bool $all_str    是否全部以字符串的格式显示
     */
    public function setContent(string $data)
    {
        $this->initBaseStyle();
        $section = self::sectionInstance();
        // 用换行符拆分成数组
        $data_arr = explode(PHP_EOL, $data);
        foreach ($data_arr as $key => $value) {
            $section->addText($value, array_merge($this->bodyStyle, ['size' => Constants::S3]), $this->bodyFormat);
        }
        return $this;
    }

    ////////////////////////////////////////////////// 输出文件 START //////////////////////////////////////////////////

    /**
     * 获取文件类型
     *
     * @param object $fileName 文件名
     * @return mixed
     **/
    public function getType($fileName)
    {
        // 获取文件后缀
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // ['ODText', 'RTF', 'Word2007', 'HTML', 'PDF']
        $type = "Word2007";
        switch ($ext) {
            case 'odt':
                $type = "ODText";
                break;
            case 'rtf':
                $type = "RTF";
                break;
            case 'doc':
            case 'docx':
                $type = "Word2007";
                break;
            case 'htm':
            case 'html':
                $type = "HTML";
                break;
            case 'pdf':
                $type = "PDF";
                $root = $this->getRootPath();
                // https://phpoffice.github.io/PHPWord/usage/writers.html
                Settings::setPdfRenderer(Settings::PDF_RENDERER_DOMPDF, "{$root}/vendor/dompdf");
                // php ./vendor/dfer/tools/documents/dompdf-fonts/load_font.php system_fonts simfang
                Settings::setPdfRendererOptions([
                    'font' => 'simfang'
                ]);
                $this->callback = function ($inputHTML) {
                    $inputHTML = $this->strReplace($inputHTML, ['&nbsp;'], [null]);
                    return $inputHTML;
                };
                break;
            default:
                break;
        }

        return $type;
    }

    /**
     * 回调
     * 为了生成 PDF，PhpWord 对象在生成 PDF 之前传递 HTML。可以使用回调来修改此 HTML。
     * @param object $callback_function 匿名方法
     * @return mixed
     **/
    public function setCallback(Closure $callback_function)
    {
        $this->callback = $callback_function;
        return $this;
    }

    /**
     * 直接获取文件，不保存
     * 必须以打开新页面的形式调用，比如：a标签的内部跳转或者外部跳转，js的`window.open`或者`location.href`
     * @param {Object} string $fileName    保存的文件名
     * @param {Object} int $max_age 浏览器里的文件缓存时间（秒）
     */
    public function getFile(string $fileName = 'test.docx', int $max_age = 60)
    {
        // var_dump($this->getType($fileName),$this->callback);die;
        header("Content-Disposition: attachment;filename={$fileName}");
        header("Cache-Control:max-age={$max_age}");
        $writer = IOFactory::createWriter(self::wordInstance(), $this->getType($fileName));
        if ($this->callback)
            $writer->setEditCallback(function ($content) {
                return call_user_func($this->callback, $content);
            });
        $writer->save('php://output');
        exit;
    }

    /**
     * 获取base64数据流
     * @param {Object} string $fileName
     */
    public function saveStream(string $fileName = 'test.xlsx')
    {
    }

    /**
     * 保存文件
     * @param {Object} string $fileName    文件名称
     */
    public function saveFile(string $fileName = 'test.xlsx')
    {
    }

    /**获取文件路径和名称
     * @param {Object} string $fileName    文件名
     * @param {Object} string $path 保存路径    eg:$path = Env::get('excel.savePath', 'excel/');
     */
    protected function getFileName(string $fileName, string $path = 'excel/')
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return $path . $fileName;
    }

    //////////////////////////////////////////////////  输出文件 END  //////////////////////////////////////////////////
}
