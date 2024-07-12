<?php

namespace app\models;

use app\components\response\ResponseCodesModels;
use app\models\structure\OrderTrackingStructure;

class OrderTracking extends OrderTrackingStructure
{
    public const STATUS_AWAITING = 'awaiting';
    public const STATUS_IN_BAYER_WAREHOUSE = 'in_bayer_warehouse';
    public const STATUS_SENT_TO_DESTINATION = 'item_sent';
    public const STATUS_IN_FULFILLMENT_WAREHOUSE = 'in_fulfillment_warehouse';
    public const STATUS_READY_TRANSFERRING_TO_MARKETPLACE = 'ready_transferring_to_marketplace';
    public const STATUS_PARTIALLY_DELIVERED_TO_MARKETPLACE = 'partially_delivered_to_marketplace';
    public const STATUS_FULLY_SHIPPED_MARKETPLACE = 'fully_shipped_marketplace';
    public const STATUS_ARRIVED = 'item_arrived';

    public const STATUS_ALL = [
        self::STATUS_AWAITING,
        self::STATUS_IN_BAYER_WAREHOUSE,
        self::STATUS_SENT_TO_DESTINATION,
        self::STATUS_IN_FULFILLMENT_WAREHOUSE,
        self::STATUS_READY_TRANSFERRING_TO_MARKETPLACE,
        self::STATUS_PARTIALLY_DELIVERED_TO_MARKETPLACE,
        self::STATUS_FULLY_SHIPPED_MARKETPLACE,
        self::STATUS_ARRIVED,
    ];

    public static function apiCodes(): ResponseCodesModels
    {
        return ResponseCodesModels::getStatic();
    }
    public static function getStatusMap()
    {
        return [
            ['key' => self::STATUS_AWAITING, 'translate' => 'Ожидание'],
            [
                'key' => self::STATUS_IN_BAYER_WAREHOUSE,
                'translate' => 'На складе у bayer',
            ],
            [
                'key' => self::STATUS_SENT_TO_DESTINATION,
                'translate' => 'Товар отправлен',
            ],
            ['key' => self::STATUS_ARRIVED, 'translate' => 'Товар прибыл'],

            [
                'key' => self::STATUS_IN_FULFILLMENT_WAREHOUSE,
                'translate' => 'Прибыл к фулфиллменту',
            ],

            [
                'key' => self::STATUS_READY_TRANSFERRING_TO_MARKETPLACE,
                'translate' => 'Готов к отправке на маркетплейс',
            ],

            [
                'key' => self::STATUS_PARTIALLY_DELIVERED_TO_MARKETPLACE,
                'translate' => 'Частично отгружен на маркетплейс',
            ],
            [
                'key' => self::STATUS_FULLY_SHIPPED_MARKETPLACE,
                'translate' => 'Полностью отгружен на маркетплейс',
            ],
        ];
    }
}
