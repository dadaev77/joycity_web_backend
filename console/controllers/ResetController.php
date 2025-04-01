<?php

namespace app\console\controllers;

use yii\console\Controller;

/**
 * Контроллер для сброса базы данных
 * 
 * Console::FG_BLACK
 * Console::FG_BLUE
 * Console::FG_CYAN
 * Console::FG_GREEN
 * Console::FG_PURPLE
 * Console::FG_RED
 * Console::FG_YELLOW
 * Console::FG_GREY
 * 
 */

class ResetController extends Controller
{
    /**
     * @var array Список таблиц для обработки
     */
    private $tables = [];
    private $attachments = [];

    /**
     * Сброс базы данных
     * @return void
     */
    public function actionDatabase()
    {
        $this->stdout("Сброс базы данных...\n", \yii\helpers\Console::FG_YELLOW);
        $this->getTables()
            ->excludeTables(
                [
                    'app_option',
                    'category',
                    'delivery_point_address',
                    'heartbeat',
                    'migration',
                    'privacy_policy',
                    'rate',
                    'type_delivery',
                    'type_delivery_link_category',
                    'type_delivery_point',
                    'type_delivery_price',
                    'type_packaging',
                    'user',
                    'user_link_category',
                    'user_link_type_delivery',
                    'user_link_type_packaging',
                    'user_settings',
                ]
            )
            ->truncateTables();
        $this->stdout("Сброс базы данных завершен.\n", \yii\helpers\Console::FG_GREEN);
    }

    /**
     * Удаление вложений
     */
    public function actionAttachments()
    {
        if (!is_dir(dirname(__DIR__, 2) . '/entrypoint/api/attachments')) {
            $this->stdout("Папка с вложениями не существует.\n", \yii\helpers\Console::FG_RED);
            return;
        }
        $this->getAttachments()->deleteAttachments();
        $this->stdout("Вложения удалены.\n", \yii\helpers\Console::FG_GREEN);
    }

    private function getAttachments()
    {
        $this->attachments = scandir(dirname(__DIR__, 2) . '/entrypoint/api/attachments');

        foreach ($this->attachments as $attachment) {
            if ($attachment !== '.' && $attachment !== '..') {
                $this->deleteAttachment($attachment);
            }
        }

        return $this;
    }

    private function deleteAttachment($attachment)
    {
        if (is_file(dirname(__DIR__, 2) . '/entrypoint/api/attachments/' . $attachment)) {
            $this->stdout("Удаление вложения $attachment...\n", \yii\helpers\Console::FG_CYAN);
            unlink(dirname(__DIR__, 2) . '/entrypoint/api/attachments/' . $attachment);
            $this->stdout("Вложение $attachment удалено.\n", \yii\helpers\Console::FG_GREEN);
        }
    }

    /**
     * Получение списка таблиц
     * @return $this
     */
    private function getTables()
    {
        $this->tables = [
            'app_option',
            'attachment',
            'buyer_delivery_offer',
            'buyer_offer',
            'category',
            'charges',
            'chats',
            'delivery_point_address',
            'feedback_buyer',
            'feedback_buyer_link_attachment',
            'feedback_product',
            'feedback_product_link_attachment',
            'feedback_user',
            'feedback_user_link_attachment',
            'fulfillment_inspection_report',
            'fulfillment_marketplace_transaction',
            'fulfillment_offer',
            'fulfillment_packaging_labeling',
            'fulfillment_stock_report',
            'fulfillment_stock_report_link_attachment',
            'heartbeat',
            'messages',
            'migration',
            'notification',
            'order',
            'order_distribution',
            'order_link_attachment',
            'order_rate',
            'order_tracking',
            'packaging_report_link_attachment',
            'privacy_policy',
            'product',
            'product_inspection_report',
            'product_link_attachment',
            'product_stock_report',
            'product_stock_report_link_attachment',
            'push_notification',
            'queue',
            'rate',
            'type_delivery',
            'type_delivery_link_category',
            'type_delivery_point',
            'type_delivery_price',
            'type_packaging',
            'user',
            'user_link_category',
            'user_link_type_delivery',
            'user_link_type_packaging',
            'user_settings',
            'user_verification_request',
            'waybill',
        ];
        return $this;
    }

    /**
     * Исключение таблиц из списка
     * @return $this
     */
    private function excludeTables(array $tables)
    {
        $this->tables = array_diff($this->tables, $tables);
        return $this;
    }

    /**
     * Очистка таблиц
     * @return $this
     */
    private function truncateTables()
    {
        \Yii::$app->db->createCommand("SET FOREIGN_KEY_CHECKS = 0;")->execute();
        foreach ($this->tables as $table) {
            $this->truncateTable($table);
        }
        \Yii::$app->db->createCommand("SET FOREIGN_KEY_CHECKS = 1;")->execute();
        return $this;
    }

    private function truncateTable($table)
    {
        $this->stdout("Сброс таблицы $table...\n", \yii\helpers\Console::FG_CYAN);
        \Yii::$app->db->createCommand()->truncateTable($table)->execute();
        $this->stdout("Сброс таблицы $table завершен.\n", \yii\helpers\Console::FG_GREEN);
        return $this;
    }
}
