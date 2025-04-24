<?php

namespace app\components\excel\tables;

use app\components\excel\TableDefinition;

class DeliveryPointTypeTable extends TableDefinition
{
    public function getSheetName(): string
    {
        return 'Delivery Point Types';
    }

    public function getHeaders(): array
    {
        return [
            'A' => 'ID',
            'B' => 'Название',
            'C' => 'Name',
            'D' => '名称',
        ];
    }

    public function getData(): array
    {
        $result = [];
        foreach (\app\models\TypeDeliveryPoint::find()->all() as $item) {
            $result[] = [
                'A' => $item->id,
                'B' => $item->ru_name,
                'C' => $item->en_name,
                'D' => $item->zh_name,
            ];
        }
        return $result;
    }

    public function getFields(): array
    {
        return [
            'A' => 'id',
            'B' => 'ru_name',
            'C' => 'en_name',
            'D' => 'zh_name',
        ];
    }

    public function getDataStyles(): array
    {
        return [
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];
    }
}
