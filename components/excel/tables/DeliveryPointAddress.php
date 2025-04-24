<?php

namespace app\components\excel\tables;

use app\components\excel\TableDefinition;

class DeliveryPointAddress extends TableDefinition
{
    public function getSheetName(): string
    {
        return 'Delivery Point Addresses';
    }

    public function getHeaders(): array
    {
        return [
            'A' => 'ID',
            'B' => 'Delivery point type ID',
            'C' => 'Address',
        ];
    }

    public function getData(): array
    {
        $result = [];
        foreach (\app\models\DeliveryPointAddress::find()->all() as $item) {
            $dt = \app\models\TypeDeliveryPoint::findOne($item->type_delivery_point_id);
            $result[] = [
                "A" => $item->id,
                "B" => $dt->ru_name . ' | ' . $dt->en_name . ' | ' . $dt->zh_name . ' (ID: ' . $dt->id . ')',
                "C" => $item->address,
            ];
        }
        return $result;
    }

    public function getFields(): array
    {
        return [
            "A" => 'id',
            "B" => 'dpt',
            "C" => 'address',
        ];
    }

    public function getDataStyles(): array
    {
        return [
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];
    }
}
