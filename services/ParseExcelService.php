<?php

namespace app\services;

use Yii;
use Throwable;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\RowIterator;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use yii\web\UploadedFile;

class ParseExcelService
{
    private static $fields = [
        'photo',
        'name',
        'description',
        'category_id',
        'subcategory_id',
        'quantity',
        'price',
        'delivery_type_id',
        'delivery_point_type_id',
        'delivery_point_address_id',
        'packaging_type_id',
        'packages_quantity',
        'deep_inspection',
    ];
    private static $sheetIndex = 1;
    private static $allowedExt = ['xls', 'xlsx'];
    private static $allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    private static $response = [];



    public static function parseExcel($file)
    {
        $file = UploadedFile::getInstanceByName('file');
        if (!$file) self::$response['error'][] = 'File not found';
        $ext = $file->getExtension();
        $type = $file->type;

        if (!in_array($ext, self::$allowedExt)) self::$response['error'][] = 'File extension not allowed';
        if (!in_array($type, self::$allowedTypes)) self::$response['error'][] = 'File type not allowed';


        $spreadsheet = self::loadSpreadsheet($file->tempName);
        $sheet = $spreadsheet->getSheet(self::$sheetIndex);
        $rawOrderData = [];

        $rowIterator = $sheet->getRowIterator(2);
        foreach ($rowIterator as $row) {
            $row = $row->getCellIterator();
            $orderData = [];
            foreach ($row as $cell) {
                $orderData[] = $cell->getValue();
            }
            $rawOrderData[] = array_combine(self::$fields, $orderData);
        }

        $validatedOrdersData = self::validateOrdersData($rawOrderData);
        $createdOrdersIds = self::createOrders($validatedOrdersData);

        return self::$response;
    }


    private static function validateOrdersData(array $rawOrderData)
    {
        $validatedOrdersData = [];

        foreach ($rawOrderData as $index => $singleOrder) {
            foreach (self::$fields as $field) {
                if (empty($singleOrder[$field]) && $field !== 'deep_inspection') {
                    self::$response['error'][] = 'Пустое значение в строке ' . ($index + 2) . ' для поля ' . $field;
                }
            }

            $images = $singleOrder['photo'];
            $images = explode(';', $images);
            $images = array_map(function ($image) {
                return trim($image);
            }, $images);

            foreach ($images as $image) {
                if (!filter_var($image, FILTER_VALIDATE_URL)) {
                    self::$response['error'][] = 'Неверный URL изображения в строке ' . ($index + 2);
                } else {
                    $headers = get_headers($image);
                    if (strpos($headers[0], '200') === false) {
                        self::$response['error'][] = 'Изображение недоступно в строке ' . ($index + 2);
                    }
                }
            }

            $categoryId = $singleOrder['category_id'];
            $subcategoryId = $singleOrder['subcategory_id'];

            $validCategories = self::checkCategories($categoryId, $subcategoryId);

            if (!$validCategories) {
                self::$response['error'][] = 'Неверные категории в строке ' . ($index + 2);
                continue;
            }

            $deepInspection = intval($singleOrder['deep_inspection']);
            if ($deepInspection !== 0 && $deepInspection !== 1) {
                self::$response['error'][] = 'Неверное значение deep_inspection в строке ' . ($index + 2);
            }

            $validatedOrdersData[] = [
                'images' => $images,
                'name' => $singleOrder['name'],
                'description' => $singleOrder['description'],
                'category_id' => $categoryId,
                'subcategory_id' => $subcategoryId,
                'quantity' => $singleOrder['quantity'],
                'price' => $singleOrder['price'],
                'delivery_type_id' => \app\models\TypeDelivery::findOne($singleOrder['delivery_type_id'])->id,
                'delivery_point_type_id' => \app\models\TypeDeliveryPoint::findOne($singleOrder['delivery_point_type_id'])->id,
                'delivery_point_address_id' => \app\models\DeliveryPointAddress::findOne($singleOrder['delivery_point_address_id'])->id,
                'packaging_type_id' => \app\models\TypePackaging::findOne($singleOrder['packaging_type_id'])->id,
                'packages_quantity' => $singleOrder['packages_quantity'],
                'deep_inspection' => $deepInspection == 1,
            ];
        }

        return $validatedOrdersData;
    }


    private static function createOrders(array $validatedOrdersData)
    {
        $createdOrdersIds = [];

        $user = \app\models\User::getIdentity();
        foreach ($validatedOrdersData as $index => $od) {
            $randomManager = $user->getRandomManager();
            $order = new \app\models\Order();
            $order->loadDefaultValues();
            $order->setAttributes([
                'created_by' => $user->id,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => \app\models\Order::STATUS_CREATED,
                'currency' => $user->settings->currency,
                'type_delivery_point_id' => $od['delivery_point_type_id'],
                'expected_price_per_item' => $od['price'] ? (float)$od['price'] : null,
                'expected_quantity' => $od['quantity'] ?? 0,
                'expected_packaging_quantity' => $od['packages_quantity'] ?? 0,
                'type_packaging_id' => $od['packaging_type_id'] ?? null,
                'type_delivery_id' => $od['delivery_type_id'] ?? null,
                'delivery_point_address_id' => $od['delivery_point_address_id'] ?? null,
                'subcategory_id' => $od['subcategory_id'] ?? null,
                'is_need_deep_inspection' => $od['deep_inspection'] ?? 0,
                'repeat_order_id' => null,
                'repeat_images_to_keep' => null,
                'manager_id' => $randomManager->id,
            ]);

            $attachmentResponse = AttachmentService::writeFilesCollection($od['images']);

            if (!$attachmentResponse->success) {
                self::$response['error'][] = ['Ошибка при загрузке изображений в строке ' . ($index + 2), $attachmentResponse->reason];
                continue;
            }

            Yii::$app->db->beginTransaction();
            $order->linkAll('attachments', $attachmentResponse->result);
            $order->save();
            Yii::$app->db->commit();
            $createdOrdersIds[] = $order->id;
        }

        return $createdOrdersIds;
    }

    private static function checkCategories($categoryId, $subcategoryId)
    {
        $category = \app\models\Category::findOne($categoryId);
        $subcategory = \app\models\Category::findOne($subcategoryId);

        if (!$category) self::$response['error'][] = 'Категория не существует';
        if (!$subcategory) self::$response['error'][] = 'Подкатегория не существует';

        if ($subcategory->parent_id !== $category->id) self::$response['error'][] = 'Подкатегория не является дочерней для категории';

        return true;
    }

    private static function loadSpreadsheet($file)
    {
        return IOFactory::load($file);
    }
}
