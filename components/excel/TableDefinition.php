<?php

namespace app\components\excel;

use yii\db\ActiveRecord;

/**
 * 
 * @property string $getSheetName
 * @property array $getHeaders
 * @property array $getData
 * @property array $getFields
 */
abstract class TableDefinition
{
    abstract public function getSheetName(): string;

    abstract public function getHeaders(): array;

    abstract public function getData(): array;

    abstract public function getDataStyles(): array;

    abstract public function getFields(): array;

    public function getHeaderStyles(): array
    {
        return [
            'font' => [
                'bold' => true,
                'color' => [
                    'rgb' => 'FFFFFF',
                ],
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => '006BE0',
                ],
            ],

        ];
    }

    public function isAutoSizeColumns(): bool
    {
        return true;
    }

    public function getColumnWidths(): array
    {
        return [];
    }
}
