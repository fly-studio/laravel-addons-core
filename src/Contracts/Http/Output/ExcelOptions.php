<?php

namespace Addons\Core\Contracts\Http\Output;

class ExcelOptions {
    /**
     * 设置列名称，即第一行的内容，A=0，下标从0开始
     * ['ID', '名称', ...]
     * 注意：phpExcel中的A=1，$columnNames的下标从0开始是为了赋值更方便
     */
    public array $columnNames = [];
    /**
     * 设置excel的列宽度，无需设置所有列
     * @example / ['A' => '12', 'D' => '20', ...]，等同于：[0 => 12, 3 => 20, ...]
     * @example / [12, 15, ...] 可以连续设置，如设置A列宽度为12，B列为15，下标从0开始，即A=0
     */
    public array $columnWidths = [];
}