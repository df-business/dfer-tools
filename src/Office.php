<?php
namespace Dfer\Tools;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style;

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

    protected $currentRow = 1;
	
	// 新栏目
	protected $sheetIndex = 0;

    protected $width = 20;
    protected $height = 20;

    protected $hasTitle = false;

    protected $headerStyle = [];
    protected $bodyStyle = [];

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Spreadsheet();
        }

        return self::$instance;
    }

    /**
     * 获取文件路径和名称
     */
    protected function getFileName(string $fileName)
    {
        $path =Env::get('excel.savePath', 'excel/');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        
        return $path . $fileName;
    }

    /**
     * 设置sheet标题
     * @param string $sheetTitle sheet栏目标题
     * @param int $index sheet栏目编号。可添加多个栏目
     */
    public function setTitle(string $sheetTitle)
    {		
		$index=$this->sheetIndex;
        if ($index>0) {
            self::instance()->createSheet($index);
            self::instance()->setActiveSheetIndex($index);
            $this->currentRow = 1;
            $this->hasTitle = false;
        }
       
        $sheet = self::instance()->getActiveSheet();
        $sheet->setTitle($sheetTitle);
        $this->setTableTitle($sheetTitle);
		
		$this->sheetIndex++;
		
        return $this;
    }

    /**
     * 设置table标题
     * @param bool $tableTitle table标题
     */
    public function setTableTitle(string $tableTitle, int $height = 20)
    {
        $sheet = self::instance()->getActiveSheet();
        $sheet->setCellValue('A1', $tableTitle);
        $sheet->getRowDimension($this->currentRow)->setRowHeight($height);
        if (!$this->hasTitle) {
            $this->hasTitle = true;
            $this->currentRow += 1;
        }
        return $this;
    }

    /**
     * 设置特殊标题
     */
    public function setExtTable(array $data)
    {
        $sheet = self::instance()->getActiveSheet();
        $count = count($data);
        $colEn = 'A';
        for ($i=0; $i < $count; $i++) {
            $sheet->setCellValueByColumnAndRow($i + 1, $this->currentRow, $data[$i]);
            if ($i > 0) {
                $colEn++;
            }
        }
        $sheet->getRowDimension($this->currentRow)->setRowHeight($this->height);
        $range = 'A'.$this->currentRow.':'.$colEn.$this->currentRow;
        $sheet->getStyle($range)->applyFromArray($this->bodyStyle);
        $this->currentRow += 1;
        return $this;
    }

    /**
     * 设置cell宽度和高度
     */
    public function setWidthAndHeight(int $width, int $height)
    {
        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    /**
     * 设置表格的样式
     */
    public function setStyle(array $headerStyle = [], array $bodyStyle = [])
    {
        $this->headerStyle = array_merge([
            'font'=>[
                'bold'=>true,
                'size'=>15,
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
            'font'=>[
                'bold'=>true,
                'size'=>10,
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
     * 初始化基础样式
     */
    protected function initStyle()
    {
        $sheet = self::instance()->getActiveSheet();
        $sheet->getDefaultRowDimension()->setRowHeight($this->height);
        $sheet->getDefaultColumnDimension()->setWidth($this->width);
    }
    
    /**
     * 设置内容
     */
    public function setContent(array $header, array $data)
    {
        $this->initStyle();
        $sheet = self::instance()->getActiveSheet();
        // 总列数
        $count = count($header);
        // 设置表头
        $start = 'A'.$this->currentRow;
        $colEn = 'A';
        for ($i=0; $i < $count; $i++) {
            $sheet->setCellValueByColumnAndRow($i + 1, $this->currentRow, $header[$i]);
            if ($i > 0) {
                $colEn++;
            }
        }
        $sheet->getRowDimension($this->currentRow)->setRowHeight($this->height);
        $this->currentRow += 1;
        // 设置数据
        for ($i=0; $i < count($data); $i++) {
            $j = 0;
            foreach ($data[$i] as $item) {
                $sheet->setCellValueByColumnAndRow($j + 1, $this->currentRow, $item);
                $j++;
            }
            $sheet->getRowDimension($this->currentRow)->setRowHeight($this->height);
            $this->currentRow += 1;
        }
        // 设置内容样式
        $end = $colEn.($this->currentRow - 1);
        $sheet->getStyle($start.':'.$end)->applyFromArray($this->bodyStyle);
        if ($this->hasTitle) {
            // 设置标题样式
            $range = 'A1'.':'.$colEn.'1';
            $sheet->getStyle($range)->applyFromArray($this->headerStyle);
            $sheet->mergeCells($range);
        }
        return $this;
    }

    /**
     * 纵向数据
     */
    public function setVContent(array $header, array $data)
    {
        $this->initStyle();
        $sheet = self::instance()->getActiveSheet();
        // 总行数
        $count = count($header);
        $offset = 1;
        if ($count > 0) {
            $offset++;
        }
        // 设置表头
        $start = 'A'.$this->currentRow;
        $colEn = 'A';
        for ($i=0; $i < $count; $i++) {
            $sheet->setCellValueByColumnAndRow(1, $this->currentRow + $i, $header[$i]);
            $sheet->getRowDimension($this->currentRow + $i)->setRowHeight($this->height);
        }
        // 设置数据
        for ($i=0; $i < count($data); $i++) {
            $j = 0;
            $colEn++;
            foreach ($data[$i] as $item) {
                $sheet->setCellValueByColumnAndRow($i + $offset, $this->currentRow + $j, $item);
                $j++;
            }
        }
        // 设置内容样式
        $end = $colEn.($count + $this->currentRow - 1);
        $sheet->getStyle($start.':'.$end)->applyFromArray($this->bodyStyle);
        if ($this->hasTitle) {
            // 设置标题样式
            $range = 'A1'.':'.$colEn.'1';
            $sheet->getStyle($range)->applyFromArray($this->headerStyle);
            $sheet->mergeCells($range);
        }
        return $this;
    }
    
    /**
     * 直接获取文件，不保存
	 * 必须以打开新页面的形式调用，比如：a标签的内部跳转或者外部跳转，js的`window.open`或者`location.href`
     */
    public function getFile(string $fileName = 'test.xlsx')
    {
        self::instance()->setActiveSheetIndex(0);
        // 获取文件后缀
        $format = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
       
        header("Content-Disposition: attachment;filename={$fileName}");
        $writer = IOFactory::createWriter(self::instance(), ucfirst($format));
        $writer->save('php://output');
        exit;
    }

    /**
     * 获取base64数据流
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
            'file' => $contentType."base64,".base64_encode($xlsData),
            'title'=> $fileName
        ];
        return $response;
    }

    /**
     * 保存文件
     * @param string $fileName 文件名称
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
        return '/'.$file;
    }
    
    /**
     * 读取文件
     * 返回拼接好的数组，可用来做数据导入
     * https://blog.csdn.net/qq_45450789/article/details/124168621
     * @param string $fileName 文件名称
     * @param string $row_index 读取的初始行编号
     *
     * $file = request()->file('file');
     * $filename = $file->getRealpath();
     *
     */
    public function readFile(string $fileName = 'test.xlsx', $col_item=['item1','item2'], $row_index=3)
    {
      
        //设置excel格式
        $format = 'xlsx';
        $reader = IOFactory::createReader(ucfirst($format));
        //载入excel文件
        $excel = $reader->load($fileName);
        //读取第一张表
        $sheet = $excel->getSheet(0);
        
        //获取总行数
        $row_num = $sheet->getHighestRow();
        //获取总列数
        $col_num = $sheet->getHighestColumn();        
        //数组形式获取表格数据
        $list = [];
        $col_index=0;
        for ($col = 'A'; $col <= $col_num; $col++) {
            $col_val=isset($col_item[$col_index])?$col_item[$col_index]:'';
            // 从指定开始获取数据
            for ($row = $row_index; $row <= $row_num; $row++) {
                $list[$row - $row_index][$col_val]= $sheet->getCell($col . $row)->getValue();
            }
            $col_index++;
        }
        return $list;
    }
}
