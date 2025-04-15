<?php

namespace app\services\order;

use app\models\Order;
use app\models\User;
use app\models\Category;
use app\services\TranslationService;
use app\services\chats\ChatService;
use app\services\notification\NotificationConstructor;
use app\services\order\OrderDistributionService;
use app\services\order\OrderStatusService;
use app\services\push\PushService;
use app\services\AttachmentService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yii;
use yii\base\Exception;
use yii\web\UploadedFile;

class OrderExcelService
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

    private function processOrderData($row, $worksheet)
    {
        $user = User::getIdentity();
        $randomManager = $user->getRandomManager();

        $productName = is_string($worksheet->getCell('B' . $row)->getValue()) ? trim($worksheet->getCell('B' . $row)->getValue()) : $worksheet->getCell('B' . $row)->getValue();
        $category = is_string($worksheet->getCell('C' . $row)->getValue()) ? trim($worksheet->getCell('C' . $row)->getValue()) : $worksheet->getCell('C' . $row)->getValue();
        $subcategory = is_string($worksheet->getCell('D' . $row)->getValue()) ? trim($worksheet->getCell('D' . $row)->getValue()) : $worksheet->getCell('D' . $row)->getValue();
        $description = is_string($worksheet->getCell('E' . $row)->getValue()) ? trim($worksheet->getCell('E' . $row)->getValue()) : $worksheet->getCell('E' . $row)->getValue();
        $quantity = (int)$worksheet->getCell('F' . $row)->getValue();
        $price = (float)$worksheet->getCell('G' . $row)->getValue();
        $deliveryType = is_string($worksheet->getCell('H' . $row)->getValue()) ? trim($worksheet->getCell('H' . $row)->getValue()) : $worksheet->getCell('H' . $row)->getValue();
        $deliveryPoint = is_string($worksheet->getCell('I' . $row)->getValue()) ? trim($worksheet->getCell('I' . $row)->getValue()) : $worksheet->getCell('I' . $row)->getValue();
        $address = is_string($worksheet->getCell('J' . $row)->getValue()) ? trim($worksheet->getCell('J' . $row)->getValue()) : $worksheet->getCell('J' . $row)->getValue();
        $packagingType = is_string($worksheet->getCell('K' . $row)->getValue()) ? trim($worksheet->getCell('K' . $row)->getValue()) : $worksheet->getCell('K' . $row)->getValue();
        $packagingQuantity = (int)$worksheet->getCell('L' . $row)->getValue();
        $deepInspection = strtolower($worksheet->getCell('M' . $row)->getValue()) === 'да';
        $photoUrl = is_string($worksheet->getCell('A' . $row)->getValue()) ? trim($worksheet->getCell('A' . $row)->getValue()) : $worksheet->getCell('A' . $row)->getValue();

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
            'created_by' => $user->id,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => Order::STATUS_CREATED,
            'currency' => $user->settings->currency,
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
            'delivery_delay_days' => 0,
            'manager_id' => $randomManager->id
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
                $orderData["product_name_$key"] = $value['name'];
                $orderData["product_description_$key"] = $value['description'];
            }
        }

        return [
            'success' => true,
            'data' => $orderData,
            'manager_id' => $randomManager->id
        ];
    }

    public function processExcelFile($file)
    {
        try {
            if (!in_array(
                $file->type,
                $this->allowedTypes
            )) throw new \Exception('Неверный формат файла. Поддерживаются только Excel файлы (.xlsx, .xls, .csv)');
            $reader = IOFactory::createReaderForFile($file->tempName);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->tempName);
            $worksheet = $spreadsheet->getActiveSheet();

            $transaction = Yii::$app->db->beginTransaction();
            $successCount = 0;
            $errors = [];
            $processedRows = 0;

            for ($row = 2; $row <= $worksheet->getHighestRow(); $row++) {
                $productName = $worksheet->getCell('B' . $row)->getValue();
                if (empty($productName)) continue;
                $processedRows++;
                $result = $this->processOrderData($row, $worksheet);
                if (!$result['success']) {
                    $errors[] = [
                        'row' => $row,
                        'errors' => $result['errors']
                    ];
                    continue;
                }

                $order = new Order();
                $order->load($result['data'], '');

                if (!$order->save()) {
                    $errors[] = [
                        'row' => $row,
                        'errors' => $order->getFirstErrors()
                    ];
                    continue;
                }

                // Обработка изображения по ссылке
                if (!empty($order->link_tz)) {
                    $attachmentResponse = AttachmentService::writeFileWithModelByPath($order->link_tz);
                    if (!$attachmentResponse->success) {
                        \Yii::$app->telegramLog->send('error', [
                            'Ошибка сохранения изображения по ссылке',
                            "Заказ №{$order->id}",
                            "Ссылка: {$order->link_tz}",
                            json_encode($attachmentResponse->reason),
                        ], 'client');
                        throw new Exception('Image save error: ' . json_encode($attachmentResponse->reason));
                    }
                    $order->linkAll('attachments', [$attachmentResponse->result]);
                }

                // Создаем чат для заказа
                ChatService::CreateGroupChat('Order ' . $order->id, $order->created_by, $order->id, [
                    'deal_type' => 'order',
                    'participants' => [$order->created_by, $result['manager_id']],
                    'group_name' => 'client_manager',
                ], true);

                // Создаем задачу на распределение
                $distribution = OrderDistributionService::createDistributionTask($order->id);
                if (!$distribution->success) {
                    \Yii::$app->telegramLog->send('error', [
                        'Ошибка создания задачи на распределение',
                        $distribution->reason,
                    ], 'client');
                    throw new Exception('Distribution error: ' . $distribution->reason);
                }

                if (!\app\controllers\CronController::actionCreate($distribution->result->id)) {
                    \Yii::$app->telegramLog->send('error', [
                        'Ошибка создания задачи cron для распределения заказа',
                        $distribution->result->id,
                    ], 'client');
                    throw new Exception('Cron task creation error: ' . $distribution->result->id);
                }

                // Отправляем уведомление менеджеру
                NotificationConstructor::orderOrderCreated($result['manager_id'], $order->id);

                $successCount++;
            }

            if (empty($errors)) {
                $transaction->commit();
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
