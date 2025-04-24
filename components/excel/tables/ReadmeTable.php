<?php

namespace app\components\excel\tables;

use app\components\excel\TableDefinition;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReadmeTable extends TableDefinition
{
    public function getSheetName(): string
    {
        return 'Readme';
    }

    public function getHeaders(): array
    {
        return [
            'A' => 'Инструкция по заполнению',
            'B' => 'Instructions for filling',
            'C' => '填写说明',
        ];
    }

    public function getData(): array
    {
        return [
            [
                'A' => $this->getRuInstructions(),
                'B' => $this->getEnInstructions(),
                'C' => $this->getZhInstructions(),
            ],
        ];
    }

    public function getFields(): array
    {
        return [
            'A' => 'ru_instructions',
            'B' => 'en_instructions',
            'C' => 'zh_instructions',
        ];
    }

    public function getHeaderStyles(): array
    {
        return [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFCCCCCC']],
            'alignment' => ['wrapText' => true, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
    }

    public function getDataStyles(): array
    {
        return [
            'alignment' => [
                'wrapText' => true,
                'vertical' => Alignment::VERTICAL_TOP,
                'indent' => 1,
            ],
        ];
    }

    public function isAutoSizeColumns(): bool
    {
        return true;
    }

    private function getRuInstructions(): string
    {
        $items = [
            "Для массового создания заказов, заполните таблицу Orders данными.",
            "После заполнения таблицы, сохраните файл и отправьте его нам.",
            "Колонки помеченные (ID) - это ID справочников этих полей. Их можно найти в соответствующих таблицах.",
            "Колонки помеченные (ID) - требуют ввода ТОЛЬКО ID из справочников.",
            "Для загрузки изображений необходимо использовать ссылки на изображения разделенные символом (;)",
            "Избегайте пустых ячеек.",
        ];
        return implode("\n", array_map(fn($item) => "• $item", $items));
    }

    private function getEnInstructions(): string
    {
        $items = [
            "• To create orders in bulk, fill the Orders table with data.",
            "• After filling out the table, save the file and send it to us.",
            "• Columns marked (ID) are the IDs of the reference fields. You can find them in the corresponding tables.",
            "• Columns marked (ID) require input of ONLY the IDs from the reference tables.",
            "• To upload images, use links to images separated by a semicolon (;).",
            "• Avoid empty cells.",
        ];
        return implode("\n", array_map(fn($item) => "• $item", $items));
    }

    private function getZhInstructions(): string
    {
        $items = [
            "• 为批量创建订单，请填写订单表格的数据。",
            "• 填写完表格后，保存文件并将其发送给我们。",
            "• 标记为（ID）的列是这些字段的参考ID。您可以在相应的表中找到它们。",
            "• 标记为（ID）的列仅需输入参考表中的ID。",
            "• 要上传图像，请使用用分号（;）分隔的图像链接。",
            "• 避免空单元格。",
        ];
        return implode("\n", array_map(fn($item) => "• $item", $items));
    }
}
