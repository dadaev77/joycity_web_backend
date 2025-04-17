<?php

namespace app\models;

use app\models\responseCodes\OrderCodes;
use app\models\structure\OrderStructure;
use app\models\OrderTracking;
use app\models\TypeDelivery;
use DateTime;

class Order extends OrderStructure
{


    // Статусы заявки
    public const STATUS_CREATED = 'created';
    public const STATUS_WAITING_FOR_BUYER_OFFER = 'waiting_for_buyer_offer';
    public const STATUS_BUYER_ASSIGNED = 'buyer_assigned';
    public const STATUS_BUYER_OFFER_CREATED = 'buyer_offer_created';
    public const STATUS_BUYER_OFFER_ACCEPTED = 'buyer_offer_accepted';
    public const STATUS_PAID = 'paid';


    // Статусы сделки
    public const STATUS_TRANSFERRING_TO_BUYER = 'transferring_to_buyer';
    public const STATUS_ARRIVED_TO_BUYER = 'arrived_to_buyer';
    public const STATUS_BUYER_INSPECTION_COMPLETE = 'buyer_inspection_complete';
    public const STATUS_TRANSFERRING_TO_WAREHOUSE = 'transferring_to_warehouse';
    public const STATUS_ARRIVED_TO_WAREHOUSE = 'arrived_to_warehouse';

    //fulfillment
    public const STATUS_TRANSFERRING_TO_FULFILLMENT = 'transferring_to_fulfillment';
    public const STATUS_ARRIVED_TO_FULFILLMENT = 'arrived_to_fulfillment';
    public const STATUS_FULFILLMENT_INSPECTION_COMPLETE = 'fulfillment_inspection_complete';
    public const STATUS_FULFILLMENT_PACKAGE_LABELING_COMPLETE = 'fulfillment_package_labeling_complete';
    public const STATUS_READY_TRANSFERRING_TO_MARKETPLACE = 'ready_transferring_to_marketplace';
    public const STATUS_PARTIALLY_DELIVERED_TO_MARKETPLACE = 'partially_delivered_to_marketplace';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_FULLY_DELIVERED_TO_MARKETPLACE = 'fully_delivered_to_marketplace';
    public const STATUS_FULLY_PAID = 'fully_paid';

    public const STATUS_CANCELLED_REQUEST = 'cancelled_request';
    public const STATUS_CANCELLED_ORDER = 'cancelled_order';
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_GROUP_ALLOWED = [
        self::STATUS_CREATED,
        self::STATUS_BUYER_ASSIGNED,
    ];

    public const STATUS_GROUP_REQUEST = [
        self::STATUS_CREATED,
        self::STATUS_BUYER_ASSIGNED,
        self::STATUS_BUYER_OFFER_CREATED,
        self::STATUS_BUYER_OFFER_ACCEPTED,
        self::STATUS_PAID,

        self::STATUS_CANCELLED_REQUEST,
    ];

    public const STATUS_GROUP_REQUEST_ACTIVE = [
        self::STATUS_CREATED,
        self::STATUS_BUYER_ASSIGNED,
        self::STATUS_BUYER_OFFER_CREATED,
        self::STATUS_BUYER_OFFER_ACCEPTED,
        self::STATUS_PAID,
    ];
    //todo удалить
    public const STATUS_GROUP_REQUEST_ACTIVE_FOR_FULFILLMENT = [
        self::STATUS_PAID,
        self::STATUS_FULLY_PAID,
    ];

    //создание оффера
    public const STATUS_GROUP_FULFILLMENT_OFFER_CREATE = [
        self::STATUS_TRANSFERRING_TO_BUYER,
        self::STATUS_ARRIVED_TO_BUYER,
        self::STATUS_TRANSFERRING_TO_WAREHOUSE,
        self::STATUS_ARRIVED_TO_WAREHOUSE,
        self::STATUS_BUYER_INSPECTION_COMPLETE,
    ];

    public const STATUS_GROUP_REQUEST_CLOSED = [self::STATUS_CANCELLED_REQUEST];

    public const STATUS_GROUP_ALL_CLOSED = [
        self::STATUS_CANCELLED_REQUEST,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED_ORDER,
    ];

    public const STATUS_GROUP_ORDER = [
        self::STATUS_TRANSFERRING_TO_BUYER,
        self::STATUS_ARRIVED_TO_BUYER,
        self::STATUS_TRANSFERRING_TO_WAREHOUSE,
        self::STATUS_ARRIVED_TO_WAREHOUSE,
        self::STATUS_BUYER_INSPECTION_COMPLETE,
        self::STATUS_TRANSFERRING_TO_FULFILLMENT,
        self::STATUS_ARRIVED_TO_FULFILLMENT,
        self::STATUS_FULFILLMENT_INSPECTION_COMPLETE,
        self::STATUS_FULFILLMENT_PACKAGE_LABELING_COMPLETE,
        self::STATUS_READY_TRANSFERRING_TO_MARKETPLACE,
        self::STATUS_PARTIALLY_DELIVERED_TO_MARKETPLACE,
        self::STATUS_FULLY_DELIVERED_TO_MARKETPLACE,
        self::STATUS_PARTIALLY_PAID,
        self::STATUS_FULLY_PAID,
        self::STATUS_CANCELLED_ORDER,
        self::STATUS_COMPLETED,
    ];

    public const STATUS_GROUP_ORDER_ACTIVE = [
        self::STATUS_TRANSFERRING_TO_BUYER,
        self::STATUS_ARRIVED_TO_BUYER,
        self::STATUS_TRANSFERRING_TO_WAREHOUSE,
        self::STATUS_ARRIVED_TO_WAREHOUSE,
        self::STATUS_BUYER_INSPECTION_COMPLETE,
        self::STATUS_TRANSFERRING_TO_FULFILLMENT,
        self::STATUS_ARRIVED_TO_FULFILLMENT,
        self::STATUS_FULFILLMENT_INSPECTION_COMPLETE,
        self::STATUS_FULFILLMENT_PACKAGE_LABELING_COMPLETE,
        self::STATUS_READY_TRANSFERRING_TO_MARKETPLACE,
        self::STATUS_PARTIALLY_DELIVERED_TO_MARKETPLACE,
        self::STATUS_FULLY_DELIVERED_TO_MARKETPLACE,
        self::STATUS_PARTIALLY_PAID,
        self::STATUS_FULLY_PAID,
    ];

    public const STATUS_GROUP_ORDER_CLOSED = [
        self::STATUS_CANCELLED_ORDER,
        self::STATUS_COMPLETED,
    ];

    public const STATUS_GROUP_ALL = [
        self::STATUS_CREATED,
        self::STATUS_WAITING_FOR_BUYER_OFFER,
        self::STATUS_BUYER_ASSIGNED,
        self::STATUS_BUYER_OFFER_CREATED,
        self::STATUS_BUYER_OFFER_ACCEPTED,
        self::STATUS_PAID,
        self::STATUS_CANCELLED_REQUEST,

        self::STATUS_TRANSFERRING_TO_BUYER,
        self::STATUS_ARRIVED_TO_BUYER,
        self::STATUS_TRANSFERRING_TO_WAREHOUSE,
        self::STATUS_ARRIVED_TO_WAREHOUSE,
        self::STATUS_BUYER_INSPECTION_COMPLETE,
        self::STATUS_TRANSFERRING_TO_FULFILLMENT,
        self::STATUS_ARRIVED_TO_FULFILLMENT,
        self::STATUS_FULFILLMENT_INSPECTION_COMPLETE,
        self::STATUS_FULFILLMENT_PACKAGE_LABELING_COMPLETE,
        self::STATUS_READY_TRANSFERRING_TO_MARKETPLACE,
        self::STATUS_PARTIALLY_DELIVERED_TO_MARKETPLACE,
        self::STATUS_FULLY_DELIVERED_TO_MARKETPLACE,
        self::STATUS_PARTIALLY_PAID,
        self::STATUS_FULLY_PAID,
        self::STATUS_CANCELLED_ORDER,
        self::STATUS_COMPLETED,
    ];




    public static function getStatusMap(array $statuses): array
    {
        return array_map(static function ($key) {
            return [
                'key' => $key,
                'translate' => self::getStatusName($key),
            ];
        }, $statuses);
    }

    public static function getStatusName($status): string
    {
        $keys = [
            self::STATUS_CREATED => 'Принята',
            self::STATUS_WAITING_FOR_BUYER_OFFER => 'В ожидании предложения',
            self::STATUS_BUYER_ASSIGNED => 'В ожидании предложения',
            self::STATUS_BUYER_OFFER_CREATED => 'Сделано предложение',
            self::STATUS_BUYER_OFFER_ACCEPTED => 'Ожидает оплаты',
            self::STATUS_PAID => 'Оплачено',
            //если есть фф

            //проведана оплата, переход в сделку
            //в пути к баеру
            self::STATUS_TRANSFERRING_TO_BUYER => 'Поставка к баеру',
            self::STATUS_ARRIVED_TO_BUYER => 'Прибыла к баеру',
            self::STATUS_BUYER_INSPECTION_COMPLETE => 'Инспекция пройдена',
            // проведение всех отчетов->отправка на склад если фф не выбран
            self::STATUS_TRANSFERRING_TO_WAREHOUSE => 'Отправлен на склад',
            self::STATUS_ARRIVED_TO_WAREHOUSE => 'Прибыл на склад',
            //если фф выбран поставляется к фф
            self::STATUS_TRANSFERRING_TO_FULFILLMENT =>
            'Поставка к фулфилменту',
            self::STATUS_ARRIVED_TO_FULFILLMENT => 'Прибыла к фулфилменту',
            // после прибытия заполнение отчетов
            self::STATUS_FULFILLMENT_INSPECTION_COMPLETE =>
            'Инспекция фулфилмента пройдена',
            self::STATUS_FULFILLMENT_PACKAGE_LABELING_COMPLETE =>
            'Маркировка и упаковка завершена',
            self::STATUS_READY_TRANSFERRING_TO_MARKETPLACE =>
            'Готово к отправке на маркетплейс',
            self::STATUS_PARTIALLY_DELIVERED_TO_MARKETPLACE =>
            'Частично доставлен на маркетплейс',
            self::STATUS_PARTIALLY_PAID => 'Частично оплачено',
            self::STATUS_FULLY_DELIVERED_TO_MARKETPLACE =>
            'Полностью доставлено на маркетплейс',
            self::STATUS_FULLY_PAID => 'Полностью оплачен',
            //варианты закрытия
            self::STATUS_CANCELLED_REQUEST => 'Отмененная заявка',
            self::STATUS_CANCELLED_ORDER => 'Отмененная сделка',
            self::STATUS_COMPLETED => 'Завершена',
        ];

        return $keys[strtolower($status)] ?? 'Неизвестный статус';
    }

    public static function apiCodes(): OrderCodes
    {
        return OrderCodes::getStatic();
    }

    /**
     * Получить накладную
     * @return \yii\db\ActiveQuery
     */
    public function getWaybill()
    {
        return $this->hasOne(Waybill::class, ['order_id' => 'id']);
    }

    /**
     * Получить первый прикрепленный файл
     *
     * @return Attachment|null
     */
    public function getFirstAttachment()
    {
        return Attachment::find()
            ->joinWith('orderLinkAttachments')
            ->where(['order_link_attachment.order_id' => $this->id])
            ->orderBy(['order_link_attachment.id' => SORT_ASC])
            ->one();
    }

    public function getOrderRate()
    {
        return $this->hasOne(OrderRate::class, ['order_id' => 'id']);
    }

    public function getOrderTrackings()
    {
        return $this->hasMany(OrderTracking::class, ['order_id' => 'id']);
    }

    public function getCurrentMarkupSum(): float
    {
        if ($this->status === self::STATUS_TRANSFERRING_TO_BUYER) {
            return $this->service_markup_sum;
        }
        
        $priceProduct = $this->price_product;
        if ($this->currency !== $this->createdBy->getSettings()->currency) {
            $priceProduct = RateService::convertValue(
                $this->price_product,
                $this->currency,
                $this->createdBy->getSettings()->currency
            );
        }
        
        return $this->total_quantity * $priceProduct * ($this->getCurrentMarkup() / 100);
    }

    /**
     * Фиксирует наценку при переходе в статус transferring_to_buyer
     * @return bool
     */
    public function fixMarkup(): bool
    {
        if ($this->status === self::STATUS_TRANSFERRING_TO_BUYER) {
            $this->service_markup = $this->getCurrentMarkup();
            $this->service_markup_sum = $this->getCurrentMarkupSum();
            return $this->save();
        }
        
        return false;
    }
}
