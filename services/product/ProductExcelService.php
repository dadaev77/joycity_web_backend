<?php

namespace app\services\product;

use app\models\Product;
use app\models\User;
use app\models\Category;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
        // Получаем значения из Excel в соответствии с шаблоном
        $photoUrl = trim($worksheet->getCell('A' . $row)->getValue());
        $name = trim($worksheet->getCell('B' . $row)->getValue());
        $category = trim($worksheet->getCell('C' . $row)->getValue());
        $subcategory = trim($worksheet->getCell('D' . $row)->getValue());
        $description = trim($worksheet->getCell('E' . $row)->getValue());
        $quantity = (int)$worksheet->getCell('F' . $row)->getValue();
        $price = (float)$worksheet->getCell('G' . $row)->getValue();
        $deliveryType = trim($worksheet->getCell('H' . $row)->getValue());
        $deliveryPoint = trim($worksheet->getCell('I' . $row)->getValue());
        $address = trim($worksheet->getCell('J' . $row)->getValue());
        $packagingType = trim($worksheet->getCell('K' . $row)->getValue());
        $packagingQuantity = (int)$worksheet->getCell('L' . $row)->getValue();
        $deepInspection = strtolower($worksheet->getCell('M' . $row)->getValue()) === 'да';

        // Получаем ID подкатегории
        $subcategoryId = $this->getSubcategoryId($category, $subcategory);
        if ($subcategoryId === null) {
            return [
                'success' => false,
                'errors' => [
                    'subcategory' => "Неверная подкатегория: {$subcategory} для категории {$category}"
                ]
            ];
        }

        // Формируем данные для создания товара
        $productData = [
            'name_ru' => $name,
            'name_en' => $name,
            'name_zh' => $name,
            'description_ru' => $description,
            'description_en' => $description,
            'description_zh' => $description,
            'subcategory_id' => $subcategoryId,
            'buyer_id' => Yii::$app->user->id,
            'currency' => 'RUB',
            'range_1_min' => 1,
            'range_1_max' => $quantity,
            'range_1_price' => $price,
            'product_height' => 0,
            'product_width' => 0,
            'product_depth' => 0,
            'product_weight' => 0,
            'is_deleted' => 0
        ];

        return [
            'success' => true,
            'data' => $productData,
            'photo_url' => $photoUrl
        ];
    }

    public function processExcelFile($file)
    {
        try {
            if (!in_array($file->type, $this->allowedTypes)) throw new \Exception('Неверный формат файла. Поддерживаются только Excel файлы (.xlsx, .xls, .csv)');
            
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
                    // Используем безопасное присваивание для всех полей
                    foreach ($result['data'] as $attribute => $value) {
                        $product->$attribute = $value;
                    }

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

    public function generateTemplate(): string
    {
        try {
            Yii::info('Запрос на создание шаблона Excel для товаров');

            $spreadsheet = new Spreadsheet();
            $sheet1 = $spreadsheet->setActiveSheetIndex(0);
            $sheet1->setTitle('Товары');

            // Заполняем заголовки
            $headers = [
                'A' => 'Фото',
                'B' => 'Название товара',
                'C' => 'Категория товара',
                'D' => 'Подкатегория',
                'E' => 'Описание товара',
                'F' => 'Количество товара, шт',
                'G' => 'Стоимость за единицу товара, Р',
                'H' => 'Тип доставки',
                'I' => 'Тип пункта доставки',
                'J' => 'Адрес пункта доставки',
                'K' => 'Тип упаковки',
                'L' => 'Количество упаковок, шт',
                'M' => 'Глубокая инспекция'
            ];

            foreach ($headers as $column => $header) {
                $sheet1->setCellValue($column . '1', $header);
                $sheet1->getColumnDimension($column)->setAutoSize(true);
            }

            // Создаем второй лист со справочниками
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Справочники');

            // Получаем данные из базы данных
            $categories = \app\models\Category::find()->select(['ru_name'])->column();
            $deliveryTypes = \app\models\TypeDelivery::find()->select(['ru_name'])->column();
            $deliveryPoints = \app\models\TypeDeliveryPoint::find()->select(['ru_name'])->column();
            
            $addresses = \app\models\DeliveryPointAddress::find()
                ->select(['delivery_point_address.address'])
                ->joinWith('typeDeliveryPoint')
                ->where(['delivery_point_address.is_deleted' => 0])
                ->column();
            
            $packagingTypes = \app\models\TypePackaging::find()->select(['ru_name'])->column();

            // Заполняем справочники
            $sheet2->setCellValue('A1', 'Категории');
            foreach ($categories as $index => $category) {
                $sheet2->setCellValue('A' . ($index + 2), $category);
            }

            $sheet2->setCellValue('E1', 'Типы_доставки');
            foreach ($deliveryTypes as $index => $type) {
                $sheet2->setCellValue('E' . ($index + 2), $type);
            }

            $sheet2->setCellValue('F1', 'Пункты_доставки');
            foreach ($deliveryPoints as $index => $point) {
                $sheet2->setCellValue('F' . ($index + 2), $point);
            }

            $sheet2->setCellValue('G1', 'Адреса');
            foreach ($addresses as $index => $address) {
                $sheet2->setCellValue('G' . ($index + 2), $address);
            }

            $sheet2->setCellValue('H1', 'Типы_упаковки');
            foreach ($packagingTypes as $index => $type) {
                $sheet2->setCellValue('H' . ($index + 2), $type);
            }

            // Варианты глубокой инспекции
            $sheet2->setCellValue('I1', 'Инспекция');
            $inspectionOptions = ['да', 'нет'];
            foreach ($inspectionOptions as $index => $option) {
                $sheet2->setCellValue('I' . ($index + 2), $option);
            }

            // Форматирование
            foreach (range('A', 'M') as $column) {
                $sheet1->getColumnDimension($column)->setAutoSize(true);
            }
            foreach (range('A', 'I') as $column) {
                $sheet2->getColumnDimension($column)->setAutoSize(true);
            }

            // Выделяем заголовки жирным
            $sheet1->getStyle('A1:M1')->getFont()->setBold(true);
            $sheet2->getStyle('A1:I1')->getFont()->setBold(true);

            // Добавляем выпадающие списки
            $this->addDropdownListDirect($sheet1, 'C', 2, 100, 'Справочники!$A$2:$A$' . (count($categories) + 1));
            $this->addDropdownListDirect($sheet1, 'H', 2, 100, 'Справочники!$E$2:$E$' . (count($deliveryTypes) + 1));
            $this->addDropdownListDirect($sheet1, 'I', 2, 100, 'Справочники!$F$2:$F$' . (count($deliveryPoints) + 1));
            $this->addDropdownListDirect($sheet1, 'J', 2, 100, 'Справочники!$G$2:$G$' . (count($addresses) + 1));
            $this->addDropdownListDirect($sheet1, 'K', 2, 100, 'Справочники!$H$2:$H$' . (count($packagingTypes) + 1));
            $this->addDropdownListDirect($sheet1, 'M', 2, 100, 'Справочники!$I$2:$I$3');

            // Возвращаемся к первому листу
            $spreadsheet->setActiveSheetIndex(0);

            // Создаем временный файл
            $tempFile = tempnam(sys_get_temp_dir(), 'product_template_');
            $writer = new WriterXlsx($spreadsheet);
            $writer->save($tempFile);

            Yii::info('Шаблон Excel для товаров успешно создан');

            return $tempFile;
        } catch (\Exception $e) {
            Yii::error("Ошибка при создании шаблона Excel для товаров: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }
    }

    private function addDropdownListDirect($sheet, $column, $startRow, $endRow, $range)
    {
        for ($i = $startRow; $i <= $endRow; $i++) {
            $validation = $sheet->getCell($column . $i)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1($range);
        }
    }
} 