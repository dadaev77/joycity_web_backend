<?php

namespace app\models;

use app\models\structure\NotificationStructure;

class Notification extends NotificationStructure
{
    public const ENTITY_TYPE_ORDER = 'order';
    public const ENTITY_TYPE_VERIFICATION = 'verification';

    public const EVENT_ORDER_CREATED = 'order_created';
    public const EVENT_ORDER_STATUS_CHANGE = 'order_status_change';
    public const EVENT_ORDER_WAITING_PAYMENT = 'order_waiting_payment';
    public const EVENT_VERIFICATION_CREATED = 'verification_created';
    public const EVENT_MARKETPLACE_TRANSACTION = 'marketplace_transaction';
    public const EVENT_ORDER_COMPLETED = 'completed';

    public static function getEntityTypeMap(): array
    {
        return [
            [
                'key' => self::ENTITY_TYPE_ORDER,
                'translate' => self::ENTITY_TYPE_ORDER,
            ],
            [
                'key' => self::ENTITY_TYPE_VERIFICATION,
                'translate' => self::ENTITY_TYPE_VERIFICATION,
            ],
        ];
    }

    public static function getEventMap(): array
    {
        return [
            [
                'key' => self::EVENT_ORDER_CREATED,
                'translate' => self::EVENT_ORDER_CREATED,
            ],
            [
                'key' => self::EVENT_ORDER_STATUS_CHANGE,
                'translate' => self::EVENT_ORDER_STATUS_CHANGE,
            ],
            [
                'key' => self::EVENT_VERIFICATION_CREATED,
                'translate' => self::EVENT_VERIFICATION_CREATED,
            ],
            [
                'key' => self::EVENT_MARKETPLACE_TRANSACTION,
                'translate' => self::EVENT_MARKETPLACE_TRANSACTION,
            ],
            [
                'key' => self::EVENT_ORDER_COMPLETED,
                'translate' => self::EVENT_ORDER_COMPLETED,
            ],
        ];
    }
}
