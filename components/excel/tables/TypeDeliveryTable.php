<?php

namespace app\components\excel\tables;

use app\components\excel\TableDefinition;

class TypeDeliveryTable extends TableDefinition
{
    public function getSheetName(): string
    {
        return 'Delivery Types';
    }

    public function getHeaders(): array
    {
        return [
            'A' => 'ID',
            'B' => 'Название',
            'C' => 'Name of Delivery',
            'D' => '交付名称',
        ];
    }

    public function getData(): array
    {
        $dTypes = \app\models\TypeDelivery::find()->all();
        $result = [];
        foreach ($dTypes as $dType) {
            $result[] = [
                "A" => $dType->id,
                "B" => $dType->ru_name,
                "C" => $dType->en_name,
                "D" => $dType->zh_name,
            ];
        }
        return $result;
    }

    public function getFields(): array
    {
        return [
            "A" => 'id',
            "B" => 'ru_name',
            "C" => 'en_name',
            "D" => 'zh_name',
        ];
    }

    public function getDataStyles(): array
    {
        return [
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];
    }
}
