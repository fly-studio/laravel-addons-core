<?php

namespace Addons\Core\Http\Output\Response;

use Addons\Core\File\Mimes;
use Addons\Core\Tools\Office;
use Symfony\Component\HttpFoundation\Request;
use Addons\Core\Contracts\Http\Output\ExcelOptions;
use Addons\Core\Http\Output\Response\TextResponse;
use Addons\Core\Http\Response\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfficeResponse extends TextResponse {

    protected ?ExcelOptions $excelOptions = null;

    public function getOf(): string
    {
        if ($this->of == 'auto') {
            $request = app('request');
            $of = $request->input('of', null);
            if (!in_array($of, ['csv', 'xls', 'xlsx']))
                $of = 'xlsx';

            return $of;
        }

        return $this->of;
    }

    public function getOutputData(): array|null
    {
        return $this->getData();
    }

    public function getExcelOptions(): ExcelOptions {
        if (is_null($this->excelOptions))
            $this->excelOptions = new ExcelOptions();

        return $this->excelOptions;
    }

    /**
     * 设置列名称，即第一行的内容，A=0，下标从0开始
     * ['ID', '名称', ...]
     * 注意：phpExcel中的A=1，$columnNames的下标从0开始是为了赋值更方便
     */
    public function columnNames(array $columnNames): static {
        $this->getExcelOptions()->columnNames = $columnNames;
        return $this;
    }

    /**
     * 设置excel的列宽度，无需设置所有列
     * @example / ['A' => '12', 'D' => '20', ...]，等同于：[0 => 12, 3 => 20, ...]
     * @example / [12, 15, ...] 可以连续设置，如设置A列宽度为12，B列为15，下标从0开始，即A=0
     * 注意：phpExcel中的A=1，$columnWidths的下标从0开始是为了赋值更方便
     */
    public function columnWidths(array $columnWidths): static {
        $this->getExcelOptions()->columnWidths = $columnWidths;
        return $this;
    }

    /**
     * 创建excel文件
     */
    public function buildFile(): ?string {
        $data = $this->getOutputData();
        $of = $this->getOf();
        $filename = null;

        switch ($of) {
            case 'csv':
            case 'xls':
            case 'xlsx':
                $filename = Office::$of($data, excelOptions:$this->getExcelOptions());
        }
        return $filename;
    }

    /**
     * 获取文件URL
     */
    public function toUrl(): ?string {
        $filename = $this->buildFile();

        return url(normalize_path(relative_path($filename, public_path()), '/'));
    }

    /**
     * 直接下载.
     * @param bool $forceDownloadHeader true输出application/octet-stream的Mime，false会输出excel的Mime
     */
    public function toDownload(bool $forceDownloadHeader = false): ?BinaryFileResponse {
        $filename = $this->buildFile();

        if (empty($filename)) {
            return null;
        }

        $of = $this->getOf();
        return response()->download(
            $filename,
            date('YmdHis').'.'.$of,
            $forceDownloadHeader ? [] : ['Content-Type' =>  Mimes::getInstance()->getMimeType($of)],
        )
        ->deleteFileAfterSend(true)
        ->setStatusCode($this->getStatusCode());

    }

    /**
     * 输出文件流
     */
    public function toStream(): ?StreamedResponse {
        $filename = $this->buildFile();

        if (empty($filename)) {
            return null;
        }

        $of = $this->getOf();
        return response()->stream(
            function() use ($filename) {
                $fp = fopen($filename, "rb");
                while(!feof($fp)) {
                    echo fread($fp, 1024);
                }
                fclose($fp);
                unlink($filename);
            }, $this->getStatusCode(),
            ['Content-Type' =>  Mimes::getInstance()->getMimeType($of)],
        );
    }

}
