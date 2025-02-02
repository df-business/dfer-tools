<?php

/**
 * +----------------------------------------------------------------------
 * | 电子表格类
 * | 支持xlsx、xls、xml、html、csv、ods文件
 * | composer require phpoffice/phpspreadsheet="^1.29"
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

use stdClass;
use PhpOffice\PhpSpreadsheet\{Spreadsheet, IOFactory, Style};
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Dfer\Tools\Statics\Common;

class Excel
{
    protected static $spreadsheetInstance;
    // 当前行
    protected $currentRow = 1;
    // 栏目编号
    protected $sheetIndex = 0;
    // 公共宽、高
    protected $width = 20;
    protected $height = 20;
    // 是否有内容标题
    protected $hasContentTitle = false;
    // 标题样式
    protected $titleStyle = [
        'font' => [
            'bold' => true,
            'size' => 15,
        ],
        'borders' => [
            'outline' => [
                'borderStyle' => 'thin',
                'color' => ['argb' => 'FF000000'],
            ],
        ],
        'alignment' => [
            'horizontal' => 'center',
            'vertical' => 'center'
        ],
    ];
    // 头部样式
    protected $headerStyle = [
        'font' => [
            'bold' => true,
            'size' => 10,
        ],
        'borders' => [
            'outline' => [
                'borderStyle' => 'thin',
                'color' => ['argb' => 'FF000000'],
            ],
        ],
        'alignment' => [
            'horizontal' => 'center',
            'vertical' => 'center'
        ],
    ];
    // 主体样式
    protected $bodyStyle = [
        'font' => [
            'bold' => false,
            'size' => 10,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => 'thin',
                'color' => ['argb' => 'FF000000'],
            ],
        ],
        'alignment' => [
            'horizontal' => 'center',
            'vertical' => 'center'
        ],
    ];

    /**
     * 静态获取Spreadsheet实例
     */
    private static function spreadsheetInstance()
    {
        if (is_null(self::$spreadsheetInstance)) {
            self::$spreadsheetInstance = new Spreadsheet();
        }

        return self::$spreadsheetInstance;
    }

    /**
     * 设置公共宽度和高度
     * @param {Object} int $width
     * @param {Object} int $height    高度 为空则使用默认值
     */
    public function setWidthAndHeight(int $width, int $height = null)
    {
        $this->width = $width;
        if ($height !== null)
            $this->height = $height;
        return $this;
    }

    /**
     * 设置表格的基本样式
     * @param {Object} array $titleStyle    标题样式
     * @param {Object} array $headerStyle    头部样式
     * @param {Object} array $bodyStyle    主体样式
     */
    public function setStyle(array $titleStyle = [], array $headerStyle = [], array $bodyStyle = [])
    {
        $this->titleStyle = array_merge($this->titleStyle, $titleStyle);
        $this->headerStyle = array_merge($this->headerStyle, $headerStyle);
        $this->bodyStyle = array_merge($this->bodyStyle, $bodyStyle);
        return $this;
    }

    /**
     * 设置基础样式
     */
    protected function initBaseStyle()
    {
        $sheet = self::spreadsheetInstance()->getActiveSheet();
        $sheet->getDefaultRowDimension()->setRowHeight($this->height);
        $sheet->getDefaultColumnDimension()->setWidth($this->width);
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

    /**
     * 设置标签标题
     * @param {Object} string $sheetTitle    sheet栏目标题
     * @param {Object} bool $hasContentTitle    是否开启内容标题
     */
    public function setTitle(string $sheetTitle, bool $hasContentTitle = false)
    {
        $index = $this->sheetIndex;
        $this->hasContentTitle = $hasContentTitle;

        if ($index > 0) {
            self::spreadsheetInstance()->createSheet($index);
            self::spreadsheetInstance()->setActiveSheetIndex($index);
            $this->currentRow = 1;
        }
        $sheet = self::spreadsheetInstance()->getActiveSheet();
        $sheet->setTitle($sheetTitle);
        $this->setTableTitle($sheetTitle);

        $this->sheetIndex++;

        return $this;
    }

    /**
     * 设置内容标题
     * @param {Object} string $tableTitle    标题
     * @param {Object} int $height    行高
     */
    public function setTableTitle(string $tableTitle, int $height = 20)
    {
        if ($this->hasContentTitle) {
            $sheet = self::spreadsheetInstance()->getActiveSheet();
            $sheet->setCellValue('A1', $tableTitle);
            // 设置行样式
            $sheet->getRowDimension($this->currentRow)->setRowHeight($height);
            $this->currentRow += 1;
        }
        return $this;
    }

    /**
     * 设置特殊标题
     * @param {Object} array $data
     */
    public function setExtTable(array $data)
    {
        $sheet = self::spreadsheetInstance()->getActiveSheet();
        $count = count($data);
        $colEn = 'A';
        for ($i = 0; $i < $count; $i++) {
            $sheet->setCellValueByColumnAndRow($i + 1, $this->currentRow, $data[$i]);
            if ($i > 0) {
                $colEn++;
            }
        }
        $sheet->getRowDimension($this->currentRow)->setRowHeight($this->height);
        $range = 'A' . $this->currentRow . ':' . $colEn . $this->currentRow;
        $sheet->getStyle($range)->applyFromArray($this->bodyStyle);
        $this->currentRow += 1;
        return $this;
    }

    /**
     * 设置内容
     * @param {Object} array $header    标题数据
     * @param {Object} array $data    主体数据
     * @param {Object} array $width    行宽
     * @param {Object} bool $all_str    是否全部以字符串的格式显示
     */
    public function setContent(array $header, array $data, array $width = [], bool $all_str = false)
    {
        $this->initBaseStyle();
        $sheet = self::spreadsheetInstance()->getActiveSheet();
        // 总列数
        $count = count($header);
        // 开始的位置
        $start_header = 'A' . $this->currentRow;
        $start_body = 'A' . ($this->currentRow + 1);
        // 列编号
        $colEn = 'A';
        // 设置首行的标题
        for ($i = 0; $i < $count; $i++) {
            $sheet->setCellValueByColumnAndRow($i + 1, $this->currentRow, $header[$i]);
            if ($i > 0) {
                $colEn++;
            }
            // 设置行宽
            if (isset($width[$i]) && $width[$i] !== null) {
                $sheet->getColumnDimension($colEn)->setWidth($width[$i]);
            }
        }
        // 设置行样式
        $sheet->getRowDimension($this->currentRow)->setRowHeight($this->height);


        // 设置内容头部样式
        $sheet->getStyle(sprintf("%s:%s", $start_header, $colEn . $this->currentRow))->applyFromArray($this->headerStyle);

        $this->currentRow += 1;
        // 设置第二行开始的主体数据
        for ($i = 0; $i < count($data); $i++) {
            $j = 0;
            foreach ($data[$i] as $item) {
                if ($item !== null) {
                    if ($all_str)
                        $sheet->setCellValueExplicitByColumnAndRow($j + 1, $this->currentRow, $item, DataType::TYPE_STRING);
                    else
                        $sheet->setCellValueByColumnAndRow($j + 1, $this->currentRow, $item);
                }
                $j++;
            }
            // 设置行样式
            $sheet->getRowDimension($this->currentRow)->setRowHeight($this->height);
            $this->currentRow += 1;
        }
        // 结束的位置
        $end = $colEn . ($this->currentRow - 1);
        // 设置内容主体公共样式
        $sheet->getStyle($start_body . ':' . $end)->applyFromArray($this->bodyStyle);

        if ($this->hasContentTitle) {
            // 设置内容标题样式
            $range_title = 'A1' . ':' . $colEn . '1';
            $sheet->getStyle($range_title)->applyFromArray($this->titleStyle);
            $sheet->mergeCells($range_title);
        }
        return $this;
    }

    /**
     * 纵向数据
     * @param {Object} array $header    标题数据
     * @param {Object} array $data    主体数据
     */
    public function setVContent(array $header, array $data)
    {
        $this->initBaseStyle();
        $sheet = self::spreadsheetInstance()->getActiveSheet();
        // 总行数
        $count = count($header);
        $offset = 1;
        if ($count > 0) {
            $offset++;
        }
        // 设置表头
        $start = 'A' . $this->currentRow;
        $colEn = 'A';
        for ($i = 0; $i < $count; $i++) {
            $sheet->setCellValueByColumnAndRow(1, $this->currentRow + $i, $header[$i]);
            $sheet->getRowDimension($this->currentRow + $i)->setRowHeight($this->height);
        }
        // 设置数据
        for ($i = 0; $i < count($data); $i++) {
            $j = 0;
            $colEn++;
            foreach ($data[$i] as $item) {
                $sheet->setCellValueByColumnAndRow($i + $offset, $this->currentRow + $j, $item);
                $j++;
            }
        }
        // 设置内容主体公共样式
        $end = $colEn . ($count + $this->currentRow - 1);
        $sheet->getStyle($start . ':' . $end)->applyFromArray($this->bodyStyle);
        if ($this->hasContentTitle) {
            // 设置内容标题样式
            $range = 'A1' . ':' . $colEn . '1';
            $sheet->getStyle($range)->applyFromArray($this->titleStyle);
            $sheet->mergeCells($range);
        }
        return $this;
    }

    /**
     * 直接获取文件，不保存
     * 必须以打开新页面的形式调用，比如：a标签的内部跳转或者外部跳转，js的`window.open`或者`location.href`
     * @param {Object} string $fileName    保存的文件名
     * @param {Object} int $max_age 文件缓存时间（秒）
     */
    public function getFile(string $fileName = 'test.xlsx', int $max_age = 60)
    {
        self::spreadsheetInstance()->setActiveSheetIndex(0);
        // 获取文件后缀
        $format = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        header("Content-Disposition: attachment;filename={$fileName}");
        // 浏览器缓存（秒）
        header("Cache-Control:max-age={$max_age}");
        $writer = IOFactory::createWriter(self::spreadsheetInstance(), ucfirst($format));
        $writer->save('php://output');
        exit;
    }

    /**
     * 获取base64数据流
     * @param {Object} string $fileName
     */
    public function saveStream(string $fileName = 'test.xlsx')
    {
        self::spreadsheetInstance()->setActiveSheetIndex(0);
        $format = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $contentType = Common::getMimeType($format);
        $writer = IOFactory::createWriter(self::spreadsheetInstance(), ucfirst($format));
        ob_start();
        $writer->save('php://output');
        $xlsData = ob_get_contents();
        ob_end_clean();
        $response = [
            'file' => "data:{$contentType};base64," . base64_encode($xlsData),
            'title' => $fileName
        ];
        return $response;
    }

    /**
     * 保存文件
     * @param {Object} string $fileName    文件名称
     */
    public function saveFile(string $fileName = 'test.xlsx')
    {
        self::spreadsheetInstance()->setActiveSheetIndex(0);
        $file = $this->getFileName($fileName);
        $format = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $writer = IOFactory::createWriter(self::spreadsheetInstance(), ucfirst($format));
        $writer->save($file);
        return '/' . $file;
    }

    /**
     * 读取文件
     * 返回拼接好的数组，可用来做数据导入
     * https://blog.csdn.net/qq_45450789/article/details/124168621
     *
     * eg:
     * $file = request()->file('file');
     * $filename = $file->getRealpath();
     *
     * @param {Object} string $fileName    文件名称
     * @param {Object} array $col_item 自定义列名    eg:['item1', 'item2']
     * @param {Object} int $row_index    读取的初始行编号
     * @param {Object} array $origin_item    需要读取原始值的列名    eg:['item1']
     * @param {Object} array $need_index    需要单独获取列表的index    eg:true
     */
    public function readFile(string $fileName = 'test.xlsx', array $col_item = [], int $row_index = 3, array $origin_item = [], bool $need_index = false)
    {
        //设置excel格式
        $format = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $reader = IOFactory::createReader(ucfirst($format));
        //载入excel文件
        $excel = $reader->load($fileName);
        //读取第一张表
        $sheet = $excel->getSheet(0);
        //获取文件总行数
        $row_num = $sheet->getHighestRow();
        //获取文件总列数
        $col_num = $sheet->getHighestColumn();

        //数组形式获取表格数据
        $list = [];
        $col_index = 0;
        // 遍历列
        for ($col = 'A'; $col <= $col_num; $col++) {
            $col_val = $col_item[$col_index] ?? '';
            $need_origin = in_array($col_val, $origin_item);
            // 遍历行
            for ($row = $row_index; $row <= $row_num; $row++) {
                // 把每一行的数据保存到自定义列名
                if ($need_origin) {
                    // 读取原始值
                    $list['index'][$row - $row_index][$col_index] = $list['name'][$row - $row_index][$col_val] = $sheet->getCell($col . $row)->getValue();
                } else {
                    // 读取格式化之后的值(时间类型的原始值不是正常的时间格式)
                    $list['index'][$row - $row_index][$col_index] = $list['name'][$row - $row_index][$col_val] = $sheet->getCell($col . $row)->getFormattedValue();
                }
            }
            $col_index++;
        }

        if ($need_index) {
            $obj = new stdClass();
            $obj->index = $list['index'];
            $obj->name = $list['name'];
            return $obj;
        } else {
            return $list;
        }
    }

    /**
     * excel时间转换
     * exl时间是1900-01-01到当前的天数，php的10位时间戳是1970-01-01到当前的秒数，相差25569天
     * @param {Object} $var    excel时间
     * @param {Object} $format    时间格式。eg:Y-m-d
     **/
    public function convertExcelTime($var = null, $format = null)
    {
        if (is_int($var)) {
            $timestrap = ($var - 25569) * 24 * 3600;
            if ($format === null) {
                return $timestrap;
            } else {
                return date($format, $timestrap);
            }
        } else
            return $var;
    }
}
