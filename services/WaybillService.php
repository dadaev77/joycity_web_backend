<?php

namespace app\services;

use app\models\Order;
use app\models\Waybill;
use Mpdf\Mpdf;
use Yii;
use yii\base\Exception;
use yii\web\NotFoundHttpException;
use app\models\User;
use app\models\TypeDelivery;
use app\models\BuyerDeliveryOffer;

class WaybillService
{
    /**
     * Создает накладную для заказа
     * 
     * @param array $data Данные для накладной
     * @return Waybill
     * @throws Exception
     */
    public static function create(array $data): Waybill
    {
        // Получаем связанные сущности
        $buyer = User::findOne($data['buyer_id'] ?? null);
        $client = User::findOne($data['client_id'] ?? null);
        $manager = User::findOne($data['manager_id'] ?? null);
        $order = Order::findOne($data['order_id'] ?? null);

        // Расчет объема
        $volume = isset($data['product_height'], $data['product_width'], $data['product_depth'], $data['amount_of_space'])
            ? ($data['product_height'] / 100) * ($data['product_width'] / 100) * ($data['product_depth'] / 100) * $data['amount_of_space']
            : 0;

        // Расчет веса и связанных расходов
        $weight = floatval($data['product_weight'] ?? 0);
        $pricePerKg = floatval($data['price_product'] ?? 0);
        $weightCosts = $weight * $pricePerKg;

        // Курс и страховка
        $course = floatval($data['course'] ?? 1);
        $insuranceRate = 0.01;
        $insuranceSum = floatval($data['price_product'] ?? 0); // в юанях
        $insuranceCosts = $insuranceSum / ($insuranceRate * $course);

        // Формирование номера накладной
        $waybillNumber = sprintf(
            "JoyCity313-%s-%s-%s",
            $client ? $client->uuid : 'UNKNOWN',
            $data['cargo_number'] ?? 'UNKNOWN',
            $data['amount_of_space'] ?? '0'
        );

        $waybillData = [
            'order_id' => $data['order_id'],
            'waybill_number' => $waybillNumber,
            'sender_name' => $buyer ? $buyer->name : '',
            'sender_phone' => $buyer ? $buyer->phone_number : '',
            'recipient_name' => $client ? $client->name : '',
            'recipient_phone' => $client ? $client->phone_number : '',
            'departure_city' => 'Иу',
            'destination_city' => 'Москва',
            'date_of_production' => date('Y:m:d H:i:s'), // TODO: заменить на дату подтверждения
            'delivery_type' => self::getDeliveryType($data),
            'course' => $course,
            'assortment' => $data['parent_category'] ?? '',
            'price_per_kg' => $pricePerKg,
            'insurance_sum_yuan' => $insuranceSum,
            'china_advance_usd' => floatval($data['china_advance'] ?? 0),
            'china_payment_usd' => floatval($data['china_payment'] ?? 0),
            'volume' => $volume,
            'weight' => $weight,
            'insurance_rate' => $insuranceRate,
            'package_expenses' => floatval($data['package_expenses'] ?? 0),
            'weight_costs' => $weightCosts,
            'insurance_costs' => $insuranceCosts,
            'total_pairs' => isset($data['total_pairs']) ? intval($data['total_pairs']) : 0, // TODO: добавить
            'total_customs_duty' => isset($data['total_customs_duty']) ? floatval($data['total_customs_duty']) : 0, // TODO: добавить
            'volume_costs' => isset($data['volume_costs']) ? floatval($data['volume_costs']) : 0, // TODO: добавить
            'total_quantity' => intval($data['amount_of_space'] ?? 0),
            'approved_by' => $manager ? $manager->name : '',
            'executor' => 'JoyCity Company',
            'total_payment' => floatval($data['package_expenses'] ?? 0) + $weightCosts + $insuranceCosts,
        ];

        // Генерируем PDF и получаем путь к файлу
        $fileName = self::generatePdf($waybillData);

        $waybillInstance = [
            'waybill_number' => $waybillNumber,
            'order_id' => $data['order_id'],
            'file_path' => $fileName,
            'created_at' => date('Y-m-d H:i:s'),
            'regenerated_at' => null,
            'editable' => true,
            'price_per_kg' => $pricePerKg,
            'course' => $course,
            'total_number_pairs' => 0,
            'total_customs_duty' => 0,
            'volume_costs' => 0,
            'date_of_production' => date('Y-m-d H:i:s'),
        ];

        // Создаем новую накладную в БД
        $waybill = new Waybill($waybillInstance);

        if (!$waybill->save()) {
            // Если не удалось сохранить в БД - удаляем файл
            self::deleteWaybillFile($fileName);
            throw new Exception('Ошибка при сохранении накладной в БД: ' . json_encode($waybill->errors));
        }

        return $waybill;
    }

    /**
     * Рассчитывает дату изготовления (дата подтверждения + 2 дня)
     */
    private static function calculateProductionDate(?Order $order): string
    {
        if (!$order || !$order->confirmation_date) {
            return date('Y-m-d H:i:s');
        }
        return date('Y-m-d H:i:s', strtotime($order->confirmation_date . ' +2 days'));
    }

    /**
     * Получает тип доставки из данных заказа
     */
    private static function getDeliveryType(array $data): string
    {
        $typeDeliveryId = $data['type_delivery_id'] ?? null;
        if (!$typeDeliveryId) {
            return '';
        }
        $typeDelivery = TypeDelivery::findOne($typeDeliveryId);
        if (!$typeDelivery) {
            return 'Неизвестный тип доставки';
        }
        return $typeDelivery->name;
    }

    /**
     * Обновляет существующую накладную
     * 
     * @param Waybill $waybill Существующая накладная
     * @param array $data Новые данные
     * @return Waybill
     * @throws Exception
     */
    public static function update(Waybill $waybill, array $data): Waybill
    {
        $bdo = BuyerDeliveryOffer::findOne(['order_id' => $data['order_id']]);
        $buyer = User::findOne($data['buyer_id']);
        $client = User::findOne($data['client_id']);
        $manager = User::findOne($data['manager_id']);

        // Расчет объема
        $volume = isset($bdo->product_height, $bdo->product_width, $bdo->product_depth, $bdo->amount_of_space)
            ? ($bdo->product_height / 100) * ($bdo->product_width / 100) * ($bdo->product_depth / 100) * $bdo->amount_of_space
            : 0;

        // Расчет веса и связанных расходов
        $weight = $bdo->product_weight ?? 0;
        $pricePerKg = $waybill->price_per_kg; // USD
        $weightCosts = $weight * $pricePerKg;

        // Курс и страховка
        $course = $waybill->course;
        $insuranceRate = 0.01;
        $insuranceSum = $bdo->price_product; // в юанях
        $insuranceCosts = $insuranceSum / ($insuranceRate * $course);

        $waybillData = [
            // Общие данные
            'order_id' => $data['order_id'],
            'waybill_number' => $waybill->waybill_number,
            'sender_name' => $buyer ? $buyer->name : '',
            'sender_phone' => $buyer ? $buyer->phone_number : '',
            'recipient_name' => $client ? $client->name : '',
            'recipient_phone' => $client ? $client->phone_number : '',
            // Доставка
            'departure_city' => 'Иу',
            'destination_city' => 'Москва',
            'date_of_production' => date('Y:m:d H:i:s'), // TODO: заменить на дату подтверждения
            'delivery_type' => self::getDeliveryType($data),
            'course' => $waybill->course,
            'assortment' => $data['parent_category'] ?? '',
            'price_per_kg' => $waybill->price_per_kg,
            'insurance_sum_yuan' => $insuranceSum,
            'china_advance_usd' => floatval($data['china_advance'] ?? 0),
            'china_payment_usd' => floatval($data['china_payment'] ?? 0),
            'volume' => $volume,
            'weight' => $weight,
            'insurance_rate' => $insuranceRate,
            'package_expenses' => floatval($data['package_expenses'] ?? 0),
            'weight_costs' => $weightCosts,
            'insurance_costs' => $insuranceCosts,
            'total_pairs' => isset($data['total_pairs']) ? intval($data['total_pairs']) : 0, // TODO: добавить
            'total_customs_duty' => isset($data['total_customs_duty']) ? floatval($data['total_customs_duty']) : 0, // TODO: добавить
            'volume_costs' => isset($data['volume_costs']) ? floatval($data['volume_costs']) : 0, // TODO: добавить
            'total_quantity' => intval($data['amount_of_space'] ?? 0),
            'approved_by' => $manager ? $manager->name : '',
            'executor' => 'JoyCity Company',
            'total_payment' => floatval($data['package_expenses'] ?? 0) + $weightCosts + $insuranceCosts,
        ];

        // Удаляем старый файл
        if ($waybill->file_path) {
            self::deleteWaybillFile($waybill->file_path);
        }

        // Генерируем новый PDF
        $fileName = self::generatePdf($waybillData);

        // Обновляем данные накладной
        $waybill->setAttributes($waybillData);
        $waybill->file_path = $fileName;

        if (!$waybill->save()) {
            self::deleteWaybillFile($fileName);
            throw new Exception('Ошибка при обн��влении накладной в БД: ' . json_encode($waybill->errors));
        }

        return $waybill;
    }

    /**
     * Получает накладную по ID заказа
     * 
     * @param int $orderId ID заказа
     * @return Waybill
     * @throws NotFoundHttpException
     */
    public static function getByOrderId(int $orderId): Waybill
    {
        $order = Order::findOne($orderId);
        if (!$order) {
            throw new NotFoundHttpException('Заказ не найден');
        }

        $waybill = $order->waybill;
        if (!$waybill) {
            throw new NotFoundHttpException('Накладная не найдена');
        }
        return $waybill;
    }

    /**
     * Блокирует возможность редактирования накладной
     * 
     * @param int $orderId ID заказа
     * @return Waybill
     * @throws Exception
     */
    public static function lockEditing(int $orderId): Waybill
    {
        $waybill = self::getByOrderId($orderId);
        $waybill->editable = false;

        if (!$waybill->save()) {
            throw new Exception('Ошибка при блокировке редактирования накладной');
        }

        return $waybill;
    }

    /**
     * Форматирует путь к файлу накладной для отдачи клиенту
     * 
     * @param Waybill $waybill Накладная
     * @return Waybill
     */
    public static function formatFilePath(Waybill $waybill): Waybill
    {
        $waybill->file_path = $_ENV['APP_URL'] . '/uploads/waybills/' . $waybill->file_path;
        return $waybill;
    }

    /**
     * Генерирует PDF файл накладной
     * 
     * @param array $data Данные для накладной
     * @return string Имя сгенерированного файла
     * @throws Exception
     */
    private static function generatePdf(array $data): string
    {
        $fileName = 'waybill_' . uniqid() . '.pdf';
        $uploadDir = Yii::getAlias('@webroot/uploads/waybills');
        $filePath = $uploadDir . '/' . $fileName;

        // Создаем директорию если её нет
        self::ensureUploadDirectoryExists($uploadDir);

        try {
            // Генерируем PDF
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
            ]);

            // Рендерим шаблон с данными
            $content = Yii::$app->view->render('@app/views/pdf/templates/invoce', [
                'data' => $data,
            ]);

            $mpdf->WriteHTML($content);
            $mpdf->Output($filePath, 'F');

            return $fileName;
        } catch (\Exception $e) {
            throw new Exception('Ошибка при генерации PDF: ' . $e->getMessage());
        }
    }

    /**
     * Проверяет и создает директорию для накладных если её нет
     * 
     * @param string $uploadDir Путь к директории
     * @throws Exception
     */
    private static function ensureUploadDirectoryExists(string $uploadDir): void
    {
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception('Не удалось создать директорию для сохранения файлов');
            }
            chmod($uploadDir, 0777);
        }

        if (!is_writable($uploadDir)) {
            throw new Exception('Нет прав на запись в директорию: ' . $uploadDir);
        }
    }

    /**
     * Удаляет файл накладной
     * 
     * @param string $fileName Имя файла
     */
    private static function deleteWaybillFile(string $fileName): void
    {
        $filePath = Yii::getAlias('@webroot/uploads/waybills') . '/' . $fileName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
