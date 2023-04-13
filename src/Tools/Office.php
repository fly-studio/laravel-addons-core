<?php

namespace Addons\Core\Tools;

use Addons\Core\Contracts\Http\Output\ExcelOptions;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class Office {

    public static function excel(array $data, string $ext = 'xlsx', string $filepath = null, ExcelOptions $excelOptions = null): string
    {
        $excel = new Spreadsheet();
        $excel->setActiveSheetIndex(0);
        $sheet = $excel->getActiveSheet();

        // 将非标量，强制转为字符串
        array_walk($data, function(&$item){
            foreach ($item as $key => $value) {
                !is_scalar($value) && $item[$key] = @strval($value);
            }
        });

        // 设置标题栏，列宽度
        if (isset($data[0])) {
            array_unshift($data, $excelOptions->columnNames ?: array_keys($data['data'][0])); // 标题栏

            foreach ($excelOptions->columnWidths ?: [] as $col => $width) {
                $column = is_int($col) ? Coordinate::stringFromColumnIndex($col + 1) : $col;
                $sheet->getColumnDimension($column)->setWidth($width);
            }
        }

        $sheet->fromArray($data);

        if (is_null($filepath)) {
            while(
                ($filepath = utils_path('files/'.date('YmsHis_').rand(100000,999999).'.'.$ext))
                && file_exists($filepath)
            ) {}
        }

        @mkdir(dirname($filepath), 0777, true);

        switch (strtolower($ext)) {
            case 'xlsx':
                $objWriter = new Xlsx($excel);
                break;
            case 'xls':
                $objWriter = new Xls($excel);
                break;
            case 'csv':
                $objWriter = new Csv($excel);
                break;
            default:
                # code...
                break;
        }

        $objWriter->save($filepath);
        @chmod($filepath, 0777);
        return $filepath;
    }

    public static function csv(array $data, string $filepath = null, ExcelOptions $excelOptions = null): string
    {
        return self::excel($data ,'csv', $filepath, excelOptions:$excelOptions);
    }

    public static function xls(array $data, string $filepath = null, ExcelOptions $excelOptions = null): string
    {
        return self::excel($data ,'xls', $filepath, excelOptions:$excelOptions);
    }

    public static function xlsx(array $data, string $filepath = null, ExcelOptions $excelOptions = null): string
    {
        return self::excel($data ,'xlsx', $filepath, excelOptions:$excelOptions);
    }

}
