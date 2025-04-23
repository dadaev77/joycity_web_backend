<?php

namespace app\services\parse;

use Yii;
use Throwable;
use \app\models\Order;
use PhpOffice\PhpSpreadsheet\IOFactory;

class OrderExcelParserService
{

    public static function parse($file)
    {
        $orders = [];
        $errors = [];

        $spreadsheet = IOFactory::load($file->tempName);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $data = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $data[] = [
                'images' => $sheet->getCell('A' . $row)->getValue(),
                'name' => $sheet->getCell('B' . $row)->getValue(),
                'category' => $sheet->getCell('C' . $row)->getValue(),
                'subcategory' => $sheet->getCell('D' . $row)->getValue(),
                'description' => $sheet->getCell('E' . $row)->getValue(),
                'qty' => $sheet->getCell('F' . $row)->getValue(),
                'price' => $sheet->getCell('G' . $row)->getValue(),
                'delivery_type' => $sheet->getCell('H' . $row)->getValue(),
                'type_delivery_point' => $sheet->getCell('I' . $row)->getValue(),
                'delivery_address' => $sheet->getCell('J' . $row)->getValue(),
                'type_package' => $sheet->getCell('J' . $row)->getValue(),
                'package_qty' => $sheet->getCell('J' . $row)->getValue(),
                'deep_inspection' => $sheet->getCell('J' . $row)->getValue(),
            ];
        }
        $data = self::prepareData($data);

        return $data;
    }

    private static function prepareData($data)
    {
        foreach ($data as $d) {
            $d['images'] = explode(';', $d['images']);
        }

        return $data;
    }
}
