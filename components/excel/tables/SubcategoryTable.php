<?php

namespace app\components\excel\tables;

use app\components\excel\TableDefinition;

class SubcategoryTable extends TableDefinition
{
    private $parentCategories = null;

    public function getSheetName(): string
    {
        return 'Subcategories';
    }

    public function getHeaders(): array
    {
        $startLiter = "A";
        $parentCategories = $this->getParentCategories();
        $headers = [];
        foreach ($parentCategories as $item) {
            $headers[$startLiter] = $item->ru_name . " | " . $item->en_name . " | " . $item->zh_name . " | ID: " . $item->id;
            $startLiter++;
        }
        return $headers;
    }

    public function getData(): array
    {
        $parentCategories = $this->getParentCategories();
        $result = [];
        $maxSubcategories = 0;
        $subcategoriesByParent = [];

        foreach ($parentCategories as $category) {
            $subcategories = $category->subcategories;
            $subcategoriesByParent[$category->id] = array_map(function ($subcategory) {
                return [
                    'id' => $subcategory->id,
                    'ru_name' => $subcategory->ru_name,
                    'en_name' => $subcategory->en_name,
                    'zh_name' => $subcategory->zh_name,
                ];
            }, $subcategories);
            $maxSubcategories = max($maxSubcategories, count($subcategories));
        }

        for ($row = 0; $row < $maxSubcategories; $row++) {
            $rowData = [];
            foreach ($parentCategories as $index => $category) {
                $column = chr(65 + $index);
                $subcategory = $subcategoriesByParent[$category->id][$row] ?? ['id' => '', 'ru_name' => '', 'en_name' => '', 'zh_name' => ''];
                $rowData[$column] = implode(', ', array_filter([
                    $subcategory['id'],
                    $subcategory['ru_name'],
                    $subcategory['en_name'],
                    $subcategory['zh_name'],
                ]));
            }
            $result[] = $rowData;
        }

        return $result;
    }

    public function getFields(): array
    {
        $parentCategories = $this->getParentCategories();
        $fields = [];
        foreach ($parentCategories as $index => $category) {
            $fields[chr(65 + $index)] = 'combined';
        }
        return $fields;
    }

    private function getParentCategories(): array
    {
        if ($this->parentCategories === null) {
            $this->parentCategories = \app\models\Category::find()->where(['parent_id' => 1])->all();
        }
        return $this->parentCategories;
    }

    public function getDataStyles(): array
    {
        return [
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];
    }
}
