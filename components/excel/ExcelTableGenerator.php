<?php

namespace app\components\excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use app\components\excel\TableDefinition;

class ExcelTableGenerator
{
    private $spreadsheet;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
    }

    public function generate(array $tables): Spreadsheet
    {
        $isFirstSheet = true;
        foreach ($tables as $table) {
            $this->createTable($table, $isFirstSheet);
            $isFirstSheet = false;
        }
        return $this->spreadsheet;
    }

    private function createTable(TableDefinition $table, bool $useActiveSheet)
    {
        $sheet = $useActiveSheet ? $this->spreadsheet->getActiveSheet() : $this->spreadsheet->createSheet();
        $sheet->setTitle($table->getSheetName());

        $headers = $table->getHeaders();
        foreach ($headers as $column => $header) {
            $sheet->setCellValue($column . '1', $header);
            if ($table->isAutoSizeColumns()) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
        }

        $headerRange = 'A1:' . array_key_last($headers) . '1';
        $sheet->getStyle($headerRange)->applyFromArray($table->getHeaderStyles());

        $row = 2;
        $fields = $table->getFields();
        foreach ($table->getData() as $dataRow) {
            foreach ($headers as $column => $header) {
                $value = '';
                if (isset($dataRow[$column])) {
                    $value = $dataRow[$column];
                } elseif (isset($fields[$column])) {
                    $value = $dataRow[$fields[$column]] ?? '';
                } else {
                    $fieldIndex = array_search($column, array_keys($headers));
                    if ($fieldIndex !== false && isset($fields[$fieldIndex])) {
                        $value = $dataRow[$fields[$fieldIndex]] ?? '';
                    }
                }
                $sheet->setCellValue($column . $row, $value);
            }
            // Применяем стили для строки данных
            $dataRange = 'A' . $row . ':' . array_key_last($headers) . $row;
            if (method_exists($table, 'getDataStyles')) {
                $sheet->getStyle($dataRange)->applyFromArray($table->getDataStyles());
            }
            // Устанавливаем авторазмер высоты строки
            $sheet->getRowDimension($row)->setRowHeight(-1); // -1 = авторазмер
            $row++;
        }
    }
}
