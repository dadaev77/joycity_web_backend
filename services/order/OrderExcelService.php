<?php

namespace app\services\order;

use app\models\Order;
use app\models\User;
use app\models\TypeDelivery;
use app\models\TypeDeliveryPoint;
use app\models\DeliveryPointAddress;
use app\models\TypePackaging;
use app\models\Category;
use app\models\Subcategory;
use app\services\TranslationService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yii;

class OrderExcelService
{
    private $validators;

    public function __construct()
    {
        $config = require Yii::getAlias('@app/config/modelFields.php');
        $this->validators = $config['Order']['validators'];
    }

    private function getTypeDeliveryId($name)
    {
        return $this->validators['delivery_type']($name);
    }

    private function getTypeDeliveryPointId($name)
    {
        return $this->validators['delivery_point']($name);
    }

    private function getDeliveryPointAddressId($address)
    {
        return $this->validators['delivery_address']($address);
    }

    private function getTypePackagingId($name)
    {
        return $this->validators['packaging_type']($name);
    }

    private function getSubcategoryId($category, $subcategory)
    {
        if (is_numeric($subcategory)) {
            return (int)$subcategory;
        }

        // Сначала найдем категорию
        $categoryModel = Category::find()
            ->where(['or',
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
            ->andWhere(['or',
                ['en_name' => $subcategory],
                ['ru_name' => $subcategory],
                ['zh_name' => $subcategory]
            ])
            ->andWhere(['is_deleted' => 0])
            ->one();

        return $subcategoryModel ? $subcategoryModel->id : null;
    }

    private function processOrderData($row, $worksheet)
    {
        // Получаем значения из Excel
        $productName = trim($worksheet->getCell('B' . $row)->getValue());
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
        $photoUrl = trim($worksheet->getCell('A' . $row)->getValue());

        // Получаем ID для связанных таблиц
        $typeDeliveryId = $this->getTypeDeliveryId($deliveryType);
        $typeDeliveryPointId = $this->getTypeDeliveryPointId($deliveryPoint);
        $deliveryPointAddressId = $this->getDeliveryPointAddressId($address);
        $typePackagingId = $this->getTypePackagingId($packagingType);
        $subcategoryId = $this->getSubcategoryId($category, $subcategory);

        // Проверяем наличие всех необходимых ID
        $errors = [];
        if ($typeDeliveryId === null) {
            $errors['type_delivery_id'] = "Неверный тип доставки: {$deliveryType}";
        }
        if ($typeDeliveryPointId === null) {
            $errors['type_delivery_point_id'] = "Неверный тип пункта доставки: {$deliveryPoint}";
        }
        if ($deliveryPointAddressId === null) {
            $errors['delivery_point_address_id'] = "Неверный адрес пункта доставки: {$address}";
        }
        if ($typePackagingId === null) {
            $errors['type_packaging_id'] = "Неверный тип упаковки: {$packagingType}";
        }
        if ($subcategoryId === null) {
            $errors['subcategory_id'] = "Неверная подкатегория: {$subcategory} для категории {$category}";
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Формируем данные для создания заказа
        $orderData = [
            'product_name_ru' => $productName,
            'product_description_ru' => $description,
            'expected_quantity' => $quantity,
            'expected_price_per_item' => $price,
            'expected_packaging_quantity' => $packagingQuantity,
            'subcategory_id' => $subcategoryId,
            'type_packaging_id' => $typePackagingId,
            'type_delivery_id' => $typeDeliveryId,
            'type_delivery_point_id' => $typeDeliveryPointId,
            'delivery_point_address_id' => $deliveryPointAddressId,
            'is_need_deep_inspection' => $deepInspection ? 1 : 0,
            'link_tz' => $photoUrl,
            'created_by' => Yii::$app->user->id,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'created',
            'currency' => User::getIdentity()->settings->currency,
            'price_product' => 0,
            'price_inspection' => 0,
            'price_packaging' => 0,
            'price_fulfilment' => 0,
            'price_delivery' => 0,
            'total_quantity' => 0,
            'is_deleted' => 0,
            'waybill_isset' => 0,
            'client_waybill_isset' => 0,
            'delivery_days_expected' => 0,
            'delivery_delay_days' => 0
        ];

        // Переводим название и описание на другие языки
        $translation = TranslationService::translateProductAttributes($productName, $description);
        $translations = $translation->result;

        foreach ($translations as $key => $value) {
            if ($key !== 'ru') {
                $orderData["product_name_$key"] = $value['name'];
                $orderData["product_description_$key"] = $value['description'];
            }
        }

        return [
            'success' => true,
            'data' => $orderData
        ];
    }

    public function processExcelFile($file)
    {
        try {
            Yii::info('Начало обработки Excel файла: ' . $file['name']);
            
            // Проверяем, что файл был загружен
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                Yii::error('Файл не был корректно загружен');
                return [
                    'success' => false,
                    'message' => 'Файл не был корректно загружен'
                ];
            }

            // Проверяем, что файл существует и доступен для чтения
            if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
                Yii::error('Файл не существует или недоступен для чтения: ' . $file['tmp_name']);
                return [
                    'success' => false,
                    'message' => 'Файл недоступен для чтения'
                ];
            }

            Yii::info('Размер загруженного файла: ' . filesize($file['tmp_name']) . ' байт');
            
            // Увеличиваем лимиты для обработки больших файлов
            set_time_limit(300);
            ini_set('memory_limit', '256M');

            // Проверка типа файла
            $allowedTypes = [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'text/csv'
            ];

            if (!in_array($file['type'], $allowedTypes)) {
                Yii::error('Неверный тип файла: ' . $file['type']);
                return [
                    'success' => false,
                    'message' => 'Неверный формат файла. Поддерживаются только Excel файлы (.xlsx, .xls, .csv)'
                ];
            }

            Yii::info('Создание читателя Excel');
            $reader = IOFactory::createReaderForFile($file['tmp_name']);
            $reader->setReadDataOnly(true);
            
            Yii::info('Загрузка файла в память');
            $spreadsheet = $reader->load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            
            Yii::info('Количество строк в файле: ' . $worksheet->getHighestRow());

            // Начинаем транзакцию
            $transaction = Yii::$app->db->beginTransaction();

            $successCount = 0;
            $errors = [];
            $processedRows = 0;

            // Обрабатываем каждую строку, начиная со второй (пропускаем заголовки)
            for ($row = 2; $row <= $worksheet->getHighestRow(); $row++) {
                try {
                    // Получаем название товара для проверки, не пустая ли строка
                    $productName = trim($worksheet->getCell('B' . $row)->getValue());

                    if (empty($productName)) {
                        Yii::info("Пропуск пустой строки {$row}");
                        continue;
                    }

                    $processedRows++;
                    Yii::info("Обработка строки {$row}: {$productName}");

                    // Обрабатываем данные строки
                    $result = $this->processOrderData($row, $worksheet);

                    if (!$result['success']) {
                        Yii::warning("Ошибки в строке {$row}: " . json_encode($result['errors']));
                        $errors[] = [
                            'row' => $row,
                            'errors' => $result['errors']
                        ];
                        continue;
                    }

                    // Создаем новый заказ
                    $order = new Order();
                    $order->load($result['data'], '');

                    if (!$order->save()) {
                        Yii::error("Ошибка сохранения заказа в строке {$row}: " . json_encode($order->getFirstErrors()));
                        $errors[] = [
                            'row' => $row,
                            'errors' => $order->getFirstErrors()
                        ];
                        continue;
                    }

                    $successCount++;
                    Yii::info("Успешно создан заказ в строке {$row}");
                } catch (\Exception $e) {
                    Yii::error("Ошибка обработки строки {$row}: " . $e->getMessage());
                    $errors[] = [
                        'row' => $row,
                        'errors' => ['general' => $e->getMessage()]
                    ];
                }
            }

            if (empty($errors)) {
                $transaction->commit();
                Yii::info("Успешно обработан файл. Создано заказов: {$successCount}");
                return [
                    'success' => true,
                    'message' => "Успешно создано заказов: {$successCount}",
                    'debug_info' => [
                        'total_rows' => $worksheet->getHighestRow(),
                        'processed_rows' => $processedRows,
                        'success_count' => $successCount
                    ]
                ];
            } else {
                $transaction->rollBack();
                Yii::error("Ошибки при обработке файла: " . json_encode($errors));
                return [
                    'success' => false,
                    'message' => 'Обнаружены ошибки при создании заказов',
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