<?php

namespace app\components\excel\tables;

use app\components\excel\TableDefinition;

class CategoryTable extends TableDefinition
{
    public function getSheetName(): string
    {
        return 'Categories';
    }

    public function getHeaders(): array
    {
        return [
            'A' => 'ID',
            'B' => 'Название категории',
            'C' => 'Name of Category',
            'D' => '类别名称',
        ];
    }

    public function getData(): array
    {
        $data = \app\models\Category::find()->where(['parent_id' => 1])->all();

        $result = [];
        foreach ($data as $item) {
            $result[] = [
                "A" => $item->id,
                "B" => $item->ru_name,
                "C" => $item->en_name,
                "D" => $item->zh_name,
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
