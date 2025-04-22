<?php

namespace app\services;

use app\models\Order;
use app\models\Waybill;
use app\services\RateService;
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

        $waybillAttachment = '';
        $order = Order::findOne($data['order_id']);
        $product = \app\models\Product::findOne($order->product_id);

        try {
            if ($product) {
                if ($product->getAttachments()->exists()) {
                    $attachments = $product->getAttachments()->all();
                    if (!empty($attachments)) {
                        $waybillAttachment = $attachments[0]->path; // Берем первое изображение
                    }
                }
            }
            if ($order) {
                if ($order->getAttachments()->exists()) {
                    $attachments = $order->getAttachments()->all();
                    if (!empty($attachments)) {
                        $waybillAttachment = $attachments[0]->path; // Берем первое изображение
                    }
                }
            }
            $waybillAttachment = base64_encode(file_get_contents(Yii::getAlias('@webroot') . $waybillAttachment));
        } catch (Exception $e) {
            $waybillAttachment = '';
        }

        // Расчет объема
        $volume = isset($data['product_height'], $data['product_width'], $data['product_depth'], $data['amount_of_space'])
            ? ($data['product_height'] / 100) * ($data['product_width'] / 100) * ($data['product_depth'] / 100) * $data['amount_of_space']
            : 0;

        // Расчет веса и связанных расходов
        $weight = floatval($data['product_weight'] ?? 0);
        $pricePerKg = RateService::convertValue(floatval(($data['price_product'] / $weight) ?? 0), $manager->settings->currency, 'USD');
        $weightCosts = $weight * $pricePerKg;
        $weightCosts = $weightCosts * $data['amount_of_space'];

        // Курс и страховка
        $rates = RateService::getRate();
        $course = floatval($rates['USD'] / $rates['CNY']);
        $insuranceRate = 0.01;
        $insuranceSum = $data['price_product'] * $data['total_quantity'];
        $insuranceSum = RateService::convertValue(floatval($insuranceSum), $manager->settings->currency, 'CNY');
        $insuranceCosts = $insuranceSum / $course * $insuranceRate;

        // Формирование номера накладной
        // $data['cargo_number'] ?? 'UNKNOWN';

        $waybillNumber = "JoyCity313-" . ($client->uuid ?? 'UNKNOWN') . "-" . ($data['amount_of_space'] ?? '0');

        $waybillData = [
            'order_id' => $data['order_id'],
            'waybill_number' => $waybillNumber,
            'sender_name' => $buyer ? $buyer->name : 'не указано',
            'sender_phone' => $buyer ? $buyer->phone_number : '',
            'recipient_name' => $client ? $client->name : '',
            'recipient_phone' => $client ? $client->phone_number : '',
            'departure_city' => 'Иу',
            'destination_city' => 'Москва',
            'date_of_production' => date('Y-m-d', strtotime('+2 days')),
            'delivery_type' => self::getDeliveryType($data),
            'course' => $course,
            'assortment' => $order->subcategory->ru_name ?? 'Отсутствует',
            'price_per_kg' => self::formatNumber($pricePerKg),
            'insurance_sum_yuan' => self::formatNumber($insuranceSum),
            'china_advance_usd' => self::formatNumber(floatval($data['china_advance'] ?? 0)),
            'china_payment_usd' => self::formatNumber(floatval($data['china_payment'] ?? 0)),
            'volume' => self::formatNumber($volume),
            'weight' => self::formatNumber($weight),
            'insurance_rate' => $insuranceRate,
            'package_expenses' => self::formatNumber(floatval($data['package_expenses'] ?? 0)),
            'weight_costs' => self::formatNumber($weightCosts),
            'insurance_costs' => self::formatNumber($insuranceCosts),
            'total_pairs' => 0,
            'total_customs_duty' => 0,
            'volume_costs' => 0,
            'total_quantity' => intval($data['amount_of_space'] ?? 0),
            'approved_by' => $manager ? $manager->name : '',
            'executor' => 'JoyCity Company',
            'total_payment' => self::formatNumber(floatval($data['package_expenses'] ?? 0) + $weightCosts + $insuranceCosts),
            'first_attachment' => $waybillAttachment,
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
            'price_per_kg' => $pricePerKg, // in USD already
            'course' => $course,
            'total_number_pairs' => 0,
            'total_customs_duty' => 0,
            'volume_costs' => 0,
            'date_of_production' => date('Y-m-d', strtotime('+2 days')),
        ];

        // Создаем новую накладную в БД
        $waybill = new Waybill($waybillInstance);

        if (!$waybill->save()) {
            \Yii::$app->telegramLog->send('error', 'Ошибка при создании накладной по заявке ' . $data['order_id'] . ': ' . json_encode($waybill->errors));
            // Если не удалось сохранить в БД - удаляем файл
            self::deleteWaybillFile($fileName);
            throw new Exception('Ошибка при сохранении накладной в БД: ' . json_encode($waybill->errors));
        }

        // Отправляем уведомление через 2 дня
        \Yii::$app->queue->delay(2 * 24 * 60 * 60)->push(function() use ($data, $waybill) {
            $order = Order::findOne($data['order_id']);
            if ($order && $order->client) {
                // Проверяем, что прошло 2 дня с момента создания накладной
                $createdAt = new \DateTime($waybill->created_at);
                $now = new \DateTime();
                $interval = $now->diff($createdAt);
                
                if ($interval->days >= 2) {
                    \Yii::$app->telegramLog->send('info', [
                        'Накладная сформирована',
                        "ID заказа: {$order->id}",
                        "Клиент: {$order->client->name} (ID: {$order->client->id})"
                    ], 'client');
                }
            }
        });

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
            return 'Не указано';
        }

        $typeDelivery = TypeDelivery::findOne($typeDeliveryId);
        if (!$typeDelivery) {
            return 'Неизвестный тип доставки';
        }

        return $typeDelivery->ru_name;
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

        $order = Order::findOne($data['order_id']);
        $product = \app\models\Product::findOne($order->product_id);
        $waybillAttachment = '';
        try {
            if ($product) {
                if ($product->getAttachments()->exists()) {
                    $attachments = $product->getAttachments()->all();
                    if (!empty($attachments)) {
                        $waybillAttachment = $attachments[0]->path; // Берем первое изображение
                    }
                }
            }
            if ($order) {
                if ($order->getAttachments()->exists()) {
                    $attachments = $order->getAttachments()->all();
                    if (!empty($attachments)) {
                        $waybillAttachment = $attachments[0]->path; // Берем первое изображение
                    }
                }
            }
            $waybillAttachment = base64_encode(file_get_contents(Yii::getAlias('@webroot') . $waybillAttachment));
        } catch (Exception $e) {
            $waybillAttachment = '';
        }

        // Расчет объема
        $volume = isset($bdo->product_height, $bdo->product_width, $bdo->product_depth, $bdo->amount_of_space)
            ? ($bdo->product_height / 100) * ($bdo->product_width / 100) * ($bdo->product_depth / 100) * $bdo->amount_of_space
            : 0;

        // Расчет веса и связанных расходов
        $weight = $bdo->product_weight ?? 0;
        $pricePerKg = $data['price_per_kg']; // USD
        $weightCosts = $weight * $pricePerKg;
        $weightCosts = $weightCosts * $bdo->amount_of_space;

        // Курс и страховка
        $course = $data['course'];
        $insuranceRate = 0.01;
        $insuranceSumCNY = $bdo->total_quantity * $bdo->price_product;
        $insuranceSumCNY = RateService::convertValue(floatval($insuranceSumCNY), $bdo->currency, 'CNY');
        $insuranceCosts = $insuranceSumCNY / $course * $insuranceRate;

        $waybillData = [
            // Общие данные
            'order_id' => $data['order_id'],
            'waybill_number' => $waybill->waybill_number,
            'sender_name' => $buyer ? $buyer->name : 'не указано',
            'sender_phone' => $buyer ? $buyer->phone_number : 'не указано',
            'recipient_name' => $client ? $client->name : 'не указано',
            'recipient_phone' => $client ? $client->phone_number : 'не указано',
            // Доставка
            'departure_city' => 'Иу',
            'destination_city' => 'Москва',
            'date_of_production' => $data['date_of_production'],
            'delivery_type' => self::getDeliveryType($data),
            'course' => self::formatNumber($data['course']),
            'assortment' => Order::findOne($bdo->order_id)->subcategory->ru_name ?? 'Отсутствует',
            'price_per_kg' => self::formatNumber($data['price_per_kg']),
            'insurance_sum_yuan' => self::formatNumber($insuranceSumCNY),
            'china_advance_usd' => self::formatNumber(floatval($data['china_advance'] ?? 0)),
            'china_payment_usd' => self::formatNumber(floatval($data['china_payment'] ?? 0)),
            'volume' => self::formatNumber($volume),
            'weight' => self::formatNumber($weight),
            'insurance_rate' => $insuranceRate,
            'package_expenses' => self::formatNumber(floatval($bdo['package_expenses'] ?? 0)),
            'weight_costs' => self::formatNumber($weightCosts),
            'insurance_costs' => self::formatNumber($insuranceCosts),
            'total_pairs' => isset($data['total_number_pairs']) ? intval($data['total_number_pairs']) : 0,
            'total_customs_duty' => isset($data['total_customs_duty']) ? self::formatNumber(floatval($data['total_customs_duty'])) : 0,
            'volume_costs' => isset($data['volume_costs']) ? self::formatNumber(floatval($data['volume_costs'])) : 0,
            'total_quantity' => intval($bdo->amount_of_space ?? 0),
            'approved_by' => $manager ? $manager->name : '',
            'executor' => 'JoyCity Company',
            'total_payment' => self::formatNumber(floatval($bdo->package_expenses ?? 0) + $weightCosts + $insuranceCosts),
            'first_attachment' => $waybillAttachment,
        ];

        // Удаляем старый файл
        if ($waybill->file_path) {
            self::deleteWaybillFile($waybill->file_path);
        }

        // Генерируем новый PDF
        $fileName = self::generatePdf($waybillData);

        // Обновляем данные накладной
        $waybill->setAttributes([
            'file_path' => $fileName,
            'regenerated_at' => date('Y-m-d H:i:s'),
            'price_per_kg' => isset($data['price_per_kg']) ? floatval($data['price_per_kg']) : $waybill->price_per_kg,
            'course' => isset($data['course']) ? floatval($data['course']) : $waybill->course,
            'total_number_pairs' => isset($data['total_number_pairs']) ? intval($data['total_number_pairs']) : $waybill->total_number_pairs,
            'total_customs_duty' => isset($data['total_customs_duty']) ? floatval($data['total_customs_duty']) : $waybill->total_customs_duty,
            'volume_costs' => isset($data['volume_costs']) ? floatval($data['volume_costs']) : $waybill->volume_costs,
            'date_of_production' => isset($data['date_of_production']) ? date('Y-m-d H:i:s', strtotime($data['date_of_production'])) : $waybill->date_of_production,
        ]);

        if (!$waybill->save()) {
            \Yii::$app->telegramLog->send('error', 'Ошибка при обновлении накладной по заявке ' . $data['order_id'] . ': ' . json_encode($waybill->errors));
            self::deleteWaybillFile($fileName);
            throw new Exception('Ошибка при обновлении накладной в БД: ' . json_encode($waybill->errors));
        }

        return self::getByOrderId($waybill->order_id);
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
        $waybill->block_edit_date = date('Y-m-d H:i:s');

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

    /**
     * Форматирует число до 2 знаков после запятой
     */
    private static function formatNumber($number)
    {
        $rounded = round($number, 2);
        if (floor($rounded) == $rounded) {
            return (string) $rounded;
        } else {
            return rtrim(rtrim(number_format($rounded, 2, '.', ''), '0'), '.');
        }
    }
}
