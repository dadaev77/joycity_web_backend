<?php

namespace app\components\excel\tables;

use app\components\excel\TableDefinition;

class OrderTable extends TableDefinition
{
    public function getSheetName(): string
    {
        return 'Orders';
    }

    public function getHeaders(): array
    {
        return [
            'A' => 'Photo',
            'B' => 'Product name',
            'C' => 'Product description',
            'D' => 'Category ID',
            'E' => 'Subcategory ID',
            'F' => 'Quantity',
            'G' => 'Price',
            'H' => 'Delivery type ID',
            'I' => 'Delivery point type ID',
            'J' => 'Delivery point address ID',
            'K' => 'Packaging type ID',
            'L' => 'QTY of packages',
            'M' => 'Deep inspection',
        ];
    }

    public function getData(): array
    {
        return [
            [
                "A" => 'Links to photos, separated by (;)',
                "B" => 'Product name',
                "C" => 'Product description',
                "D" => 'Category (ID)',
                "E" => 'Subcategory (ID)',
                "F" => 'Quantity',
                "G" => 'Price',
                "H" => 'Delivery type (ID)',
                "I" => 'Delivery point type (ID)',
                "J" => 'Delivery point address (ID)',
                "K" => 'Packaging type (ID)',
                "L" => 'QTY of packages',
                "M" => 'bool (1 - yes, 0 - no)',
            ],
        ];
    }

    public function getFields(): array
    {
        return [
            "A" => 'photo',
            "B" => 'name',
            "C" => 'description',
            "D" => 'category_id',
            "E" => 'subcategory_id',
            "F" => 'quantity',
            "G" => 'price',
            "H" => 'delivery_type_id',
            "I" => 'delivery_point_type_id',
            "J" => 'delivery_point_address_id',
            "K" => 'packaging_type_id',
            "L" => 'packages_quantity',
            "M" => 'deep_inspection',
        ];
    }

    public function getDataStyles(): array
    {
        return [
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];
    }
}
