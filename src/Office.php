<?php

namespace Dfer\Tools;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

use think\Env;

/**
 * +----------------------------------------------------------------------
 * | 电子表格服务
 * | composer require phpoffice/phpspreadsheet
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
class Office
{
    /**
     * 实例对象
     */
    protected static $instance;

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
    protected $titleStyle = [];
    // 头部样式
    protected $headerStyle = [];
    // 主体样式
    protected $bodyStyle = [];


    /**
     * 获取对象实例
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Spreadsheet();
        }

        return self::$instance;
    }

    /**
     * 设置公共宽度和高度
     * @param {Object} int $width
     * @param {Object} int $height	高度 为空则使用默认值
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
     * @param {Object} array $titleStyle	标题样式
     * @param {Object} array $headerStyle	头部样式
     * @param {Object} array $bodyStyle	主体样式
     */
    public function setStyle(array $titleStyle = [], array $headerStyle = [], array $bodyStyle = [])
    {
        $this->titleStyle = array_merge([
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
        ], $titleStyle);
        $this->headerStyle = array_merge([
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
        ], $headerStyle);
        $this->bodyStyle = array_merge([
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
        ], $bodyStyle);
        return $this;
    }

    /**
     * 设置基础样式
     */
    protected function initBaseStyle()
    {
        $sheet = self::instance()->getActiveSheet();
        $sheet->getDefaultRowDimension()->setRowHeight($this->height);
        $sheet->getDefaultColumnDimension()->setWidth($this->width);
    }


    /**获取文件路径和名称
     * @param {Object} string $fileName
     */
    protected function getFileName(string $fileName)
    {
        $path = Env::get('excel.savePath', 'excel/');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        return $path . $fileName;
    }

    /**
     * 设置标签标题
     * @param {Object} string $sheetTitle	sheet栏目标题
     * @param {Object} bool $hasContentTitle	是否开启内容标题
     */
    public function setTitle(string $sheetTitle, bool $hasContentTitle = false)
    {
        $index = $this->sheetIndex;
        $this->hasContentTitle = $hasContentTitle;

        if ($index > 0) {
            self::instance()->createSheet($index);
            self::instance()->setActiveSheetIndex($index);
            $this->currentRow = 1;
        }
        $sheet = self::instance()->getActiveSheet();
        $sheet->setTitle($sheetTitle);
        $this->setTableTitle($sheetTitle);

        $this->sheetIndex++;

        return $this;
    }

    /**
     * 设置内容标题
     * @param {Object} string $tableTitle	标题
     * @param {Object} int $height	行高
     */
    public function setTableTitle(string $tableTitle, int $height = 20)
    {
        if ($this->hasContentTitle) {
            $sheet = self::instance()->getActiveSheet();
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
        $sheet = self::instance()->getActiveSheet();
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
     * @param {Object} array $header	标题数据
     * @param {Object} array $data	主体数据
     * @param {Object} array $width	行宽
     * @param {Object} bool $all_str	是否全部以字符串的格式显示
     */
    public function setContent(array $header, array $data, array $width = [], bool $all_str = false)
    {
        $this->initBaseStyle();
        $sheet = self::instance()->getActiveSheet();
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
     * @param {Object} array $header	标题数据
     * @param {Object} array $data	主体数据
     */
    public function setVContent(array $header, array $data)
    {
        $this->initBaseStyle();
        $sheet = self::instance()->getActiveSheet();
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
     * @param {Object} string $fileName	保存的文件名
     * @param {Object} int $max_age 文件缓存时间（秒）
     */
    public function getFile(string $fileName = 'test.xlsx', int $max_age = 60)
    {
        self::instance()->setActiveSheetIndex(0);
        // 获取文件后缀
        $format = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        header("Content-Disposition: attachment;filename={$fileName}");
        // 浏览器缓存（秒）
        header("Cache-Control:max-age={$max_age}");
        $writer = IOFactory::createWriter(self::instance(), ucfirst($format));
        $writer->save('php://output');
        exit;
    }

    /**
     * 获取base64数据流
     * @param {Object} string $fileName
     */
    public function saveStream(string $fileName = 'test.xlsx')
    {
        self::instance()->setActiveSheetIndex(0);
        $format = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $contentType = '';
        switch ($format) {
            case 'xlsx':
                $contentType = 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;';
                break;
            case 'xls':
                $contentType = 'data:application/vnd.ms-excel;';
                break;
            case 'csv':
                $contentType = 'data:text/csv;';
                break;
            default:
                return false;
                break;
        }
        $writer = IOFactory::createWriter(self::instance(), ucfirst($format));
        ob_start();
        $writer->save('php://output');
        $xlsData = ob_get_contents();
        ob_end_clean();
        $response =  [
            'file' => $contentType . "base64," . base64_encode($xlsData),
            'title' => $fileName
        ];
        return $response;
    }


    /**
     * 保存文件
     * @param {Object} string $fileName	文件名称
     */
    public function saveFile(string $fileName = 'test.xlsx')
    {
        self::instance()->setActiveSheetIndex(0);
        $file = $this->getFileName($fileName);
        $format = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($format, ['xlsx', 'xls', 'csv'])) {
            return false;
        }
        $writer = IOFactory::createWriter(self::instance(), ucfirst($format));
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
     * @param {Object} string $fileName	文件名称
     * @param {Object} array $col_item 自定义列名	eg:['item1', 'item2']
     * @param {Object} int $row_index	读取的初始行编号
     * @param {Object} array $format_item	需要格式化的列名	eg:['item1']
     */
    public function readFile(string $fileName = 'test.xlsx', array $col_item = [], int $row_index = 3, array $format_item = [])
    {
        //设置excel格式
        $format = 'xlsx';
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
            $col_val = isset($col_item[$col_index]) ? $col_item[$col_index] : '';
            $need_format = in_array($col_val, $format_item);
            // 遍历行
            for ($row = $row_index; $row <= $row_num; $row++) {
                // 把每一行的数据保存到自定义列名

                if ($need_format) {
                    // 读取格式化之后的值(时间类型的原始值不是正常的时间格式)
                    $list[$row - $row_index][$col_val] = $sheet->getCell($col . $row)->getFormattedValue();
                } else {
                    // 读取原始值
                    $list[$row - $row_index][$col_val] = $sheet->getCell($col . $row)->getValue();
                }
            }
            $col_index++;
        }

        return $list;
    }


    /**
     * excel时间转换
     * exl时间是1900-01-01到当前的天数，php的10位时间戳是1970-01-01到当前的秒数，相差25569天
     * @param {Object} $var	excel时间
     * @param {Object} $format	时间格式。eg:Y-m-d
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
