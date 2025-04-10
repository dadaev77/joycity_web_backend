<?php

namespace app\services\product;

use app\models\Product;
use app\models\User;
use app\models\Category;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yii;

class ProductExcelService
{
    private $validators;
    protected $allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'text/csv'
    ];

    public function __construct()
    {
        $config = require Yii::getAlias('@app/config/modelFields.php');
        $this->validators = $config['Product']['validators'] ?? [];
    }

    private function getSubcategoryId($category, $subcategory)
    {
        if (is_numeric($subcategory)) {
            return (int)$subcategory;
        }

        // Сначала найдем категорию
        $categoryModel = Category::find()
            ->where([
                'or',
                ['en_name' => $category],
                ['ru_name' => $category],
                ['zh_name' => $category]
            ])
            ->andWhere(['is_deleted' => 0])
            ->one();

        if (!$categoryModel) {
            return null;
        }

        // Затем найдем подкатегорию для этой категории
        $subcategoryModel = Category::find()
            ->where(['parent_id' => $categoryModel->id])
            ->andWhere([
                'or',
                ['en_name' => $subcategory],
                ['ru_name' => $subcategory],
                ['zh_name' => $subcategory]
            ])
            ->andWhere(['is_deleted' => 0])
            ->one();

        return $subcategoryModel ? $subcategoryModel->id : null;
    }

    private function processProductData($row, $worksheet)
    {
        // Получаем значения из Excel
        $productName = trim($worksheet->getCell('B' . $row)->getValue());
        $category = trim($worksheet->getCell('C' . $row)->getValue());
        $subcategory = trim($worksheet->getCell('D' . $row)->getValue());
        $description = trim($worksheet->getCell('E' . $row)->getValue());
        $range1Min = (int)$worksheet->getCell('F' . $row)->getValue();
        $range1Max = (int)$worksheet->getCell('G' . $row)->getValue();
        $range1Price = (float)$worksheet->getCell('H' . $row)->getValue();
        $range2Min = (int)$worksheet->getCell('I' . $row)->getValue();
        $range2Max = (int)$worksheet->getCell('J' . $row)->getValue();
        $range2Price = (float)$worksheet->getCell('K' . $row)->getValue();
        $range3Min = (int)$worksheet->getCell('L' . $row)->getValue();
        $range3Max = (int)$worksheet->getCell('M' . $row)->getValue();
        $range3Price = (float)$worksheet->getCell('N' . $row)->getValue();
        $range4Min = (int)$worksheet->getCell('O' . $row)->getValue();
        $range4Max = (int)$worksheet->getCell('P' . $row)->getValue();
        $range4Price = (float)$worksheet->getCell('Q' . $row)->getValue();
        $productHeight = (float)$worksheet->getCell('R' . $row)->getValue();
        $productWidth = (float)$worksheet->getCell('S' . $row)->getValue();
        $productDepth = (float)$worksheet->getCell('T' . $row)->getValue();
        $productWeight = (float)$worksheet->getCell('U' . $row)->getValue();
        $photoUrl = trim($worksheet->getCell('A' . $row)->getValue());

        // Получаем ID для связанных таблиц
        $subcategoryId = $this->getSubcategoryId($category, $subcategory);

        // Проверяем наличие всех необходимых ID
        $errors = [];
        if ($subcategoryId === null) {
            $errors['subcategory_id'] = "Неверная подкатегория: {$subcategory} для категории {$category}";
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Формируем данные для создания товара
        $productData = [
            'name_ru' => $productName,
            'description_ru' => $description,
            'subcategory_id' => $subcategoryId,
            'range_1_min' => $range1Min,
            'range_1_max' => $range1Max,
            'range_1_price' => $range1Price,
            'range_2_min' => $range2Min ?: null,
            'range_2_max' => $range2Max ?: null,
            'range_2_price' => $range2Price ?: null,
            'range_3_min' => $range3Min ?: null,
            'range_3_max' => $range3Max ?: null,
            'range_3_price' => $range3Price ?: null,
            'range_4_min' => $range4Min ?: null,
            'range_4_max' => $range4Max ?: null,
            'range_4_price' => $range4Price ?: null,
            'product_height' => $productHeight,
            'product_width' => $productWidth,
            'product_depth' => $productDepth,
            'product_weight' => $productWeight,
            'buyer_id' => Yii::$app->user->id,
            'is_deleted' => 0,
            'rating' => 0,
            'feedback_count' => 0
        ];

        // Переводим название и описание на другие языки
        $translations = [
            'ru' => [
                'name' => $productName,
                'description' => $description
            ],
            'en' => [
                'name' => $productName,
                'description' => $description
            ],
            'zh' => [
                'name' => $productName,
                'description' => $description
            ]
        ];

        foreach ($translations as $key => $value) {
            if ($key !== 'ru') {
                $productData["name_$key"] = $value['name'];
                $productData["description_$key"] = $value['description'];
            }
        }

        return [
            'success' => true,
            'data' => $productData,
            'photo_url' => $photoUrl
        ];
    }

    public function processExcelFile($file)
    {
        try {
            if (!in_array($file->type, $this->allowedTypes)) {
                throw new \Exception('Неверный формат файла. Поддерживаются только Excel файлы (.xlsx, .xls, .csv)');
            }
            
            $reader = IOFactory::createReaderForFile($file->tempName);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->tempName);
            $worksheet = $spreadsheet->getActiveSheet();

            $transaction = Yii::$app->db->beginTransaction();
            $successCount = 0;
            $errors = [];
            $processedRows = 0;

            for ($row = 2; $row <= $worksheet->getHighestRow(); $row++) {
                try {
                    $productName = trim($worksheet->getCell('B' . $row)->getValue());
                    if (empty($productName)) continue;
                    $processedRows++;
                    
                    $result = $this->processProductData($row, $worksheet);
                    if (!$result['success']) {
                        $errors[] = [
                            'row' => $row,
                            'errors' => $result['errors']
                        ];
                        continue;
                    }

                    $product = new Product();
                    $product->load($result['data'], '');

                    if (!$product->save()) {
                        $errors[] = [
                            'row' => $row,
                            'errors' => $product->getFirstErrors()
                        ];
                        continue;
                    }

                    // Обработка фото, если оно указано
                    if (!empty($result['photo_url'])) {
                        // Здесь можно добавить логику для сохранения фото
                        // Например, загрузить изображение по URL и связать его с товаром
                    }

                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $row,
                        'errors' => ['general' => $e->getMessage()]
                    ];
                }
            }

            if (empty($errors)) {
                $transaction->commit();
                return [
                    'success' => true,
                    'message' => "Успешно создано товаров: {$successCount}",
                    'debug_info' => [
                        'total_rows' => $worksheet->getHighestRow(),
                        'processed_rows' => $processedRows,
                        'success_count' => $successCount
                    ]
                ];
            } else {
                $transaction->rollBack();
                return [
                    'success' => false,
                    'message' => 'Обнаружены ошибки при создании товаров',
                    'errors' => $errors,
                    'debug_info' => [
                        'total_rows' => $worksheet->getHighestRow(),
                        'processed_rows' => $processedRows,
                        'error_count' => count($errors)
                    ]
                ];
            }
        } catch (\Throwable $e) {
            if (isset($transaction)) {
                $transaction->rollBack();
            }
            Yii::error('Критическая ошибка при обработке Excel файла: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Ошибка при обработке Excel файла',
                'error' => YII_DEBUG ? $e->getMessage() : 'Внутренняя ошибка сервера'
            ];
        }
    }
} 