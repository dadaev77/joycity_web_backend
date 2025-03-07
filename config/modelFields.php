<?php

// Базовые поля для мультиязычного контента
$multiLangFields = function ($prefix = '') {
    return [
        $prefix . 'name_ru' => [
            'type' => 'string',
            'required' => true,
            'description' => 'Название на русском',
            'aliases' => ['название', 'Название', 'наименование', 'name_ru']
        ],
        $prefix . 'description_ru' => [
            'type' => 'string',
            'required' => true,
            'description' => 'Описание на русском',
            'aliases' => ['описание', 'Описание', 'description_ru']
        ],
        $prefix . 'name_en' => [
            'type' => 'string',
            'required' => true,
            'description' => 'Название на английском',
            'aliases' => ['name_en', 'english_name']
        ],
        $prefix . 'description_en' => [
            'type' => 'string',
            'required' => true,
            'description' => 'Описание на английском',
            'aliases' => ['description_en', 'english_description']
        ],
        $prefix . 'name_zh' => [
            'type' => 'string',
            'required' => true,
            'description' => 'Название на китайском',
            'aliases' => ['name_zh', 'chinese_name']
        ],
        $prefix . 'description_zh' => [
            'type' => 'string',
            'required' => true,
            'description' => 'Описание на китайском',
            'aliases' => ['description_zh', 'chinese_description']
        ]
    ];
};

return [
    'Order' => [
        'fields' => array_merge(
            [
                'id' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'ID заказа',
                    'aliases' => ['id', 'ID']
                ]
            ],
            [
                'created_at' => [
                    'type' => 'datetime',
                    'required' => true,
                    'description' => 'Дата создания',
                    'aliases' => ['date', 'дата']
                ],
                'status' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Статус заказа',
                    'aliases' => ['order_status', 'статус', 'Статус']
                ],
                'created_by' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID создателя',
                    'aliases' => ['creator', 'создатель']
                ],
                'buyer_id' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'ID покупателя',
                    'aliases' => ['buyer', 'покупатель']
                ],
                'manager_id' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'ID менеджера',
                    'aliases' => ['manager', 'менеджер']
                ],
                'fulfillment_id' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'ID исполнителя',
                    'aliases' => ['fulfillment', 'исполнитель']
                ],
                'product_id' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'ID продукта',
                    'aliases' => ['product', 'продукт']
                ],
                'product_name_ru' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Название продукта на русском',
                    'aliases' => ['название', 'name_ru']
                ],
                'product_description_ru' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Описание продукта на русском',
                    'aliases' => ['описание', 'description_ru']
                ],
                'expected_quantity' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'Ожидаемое количество',
                    'aliases' => ['quantity', 'количество']
                ],
                'expected_price_per_item' => [
                    'type' => 'float',
                    'required' => false,
                    'description' => 'Ожидаемая цена за единицу',
                    'aliases' => ['price', 'цена']
                ],
                'expected_packaging_quantity' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'Ожидаемое количество упаковок',
                    'aliases' => ['packaging_quantity', 'количество_упаковок'],
                    'default' => 0
                ],
                'subcategory_id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID подкатегории',
                    'aliases' => ['category', 'категория']
                ],
                'type_packaging_id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID типа упаковки',
                    'aliases' => ['packaging', 'упаковка']
                ],
                'type_delivery_id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID типа доставки',
                    'aliases' => ['delivery', 'доставка']
                ],
                'type_delivery_point_id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID типа пункта доставки',
                    'aliases' => ['delivery_point', 'пункт_доставки']
                ],
                'delivery_point_address_id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID адреса доставки',
                    'aliases' => ['address', 'адрес']
                ],
                'price_product' => [
                    'type' => 'float',
                    'required' => false,
                    'description' => 'Цена продукта',
                    'aliases' => ['product_price', 'цена_продукта'],
                    'default' => 0
                ],
                'price_inspection' => [
                    'type' => 'float',
                    'required' => false,
                    'description' => 'Цена инспекции',
                    'aliases' => ['inspection_price', 'цена_инспекции'],
                    'default' => 0
                ],
                'price_packaging' => [
                    'type' => 'float',
                    'required' => false,
                    'description' => 'Цена упаковки',
                    'aliases' => ['packaging_price', 'цена_упаковки'],
                    'default' => 0
                ],
                'price_fulfilment' => [
                    'type' => 'float',
                    'required' => false,
                    'description' => 'Цена фулфилмента',
                    'aliases' => ['fulfilment_price', 'цена_фулфилмента'],
                    'default' => 0
                ],
                'price_delivery' => [
                    'type' => 'float',
                    'required' => false,
                    'description' => 'Цена доставки',
                    'aliases' => ['delivery_price', 'цена_доставки'],
                    'default' => 0
                ],
                'total_quantity' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'Общее количество',
                    'aliases' => ['total', 'всего'],
                    'default' => 0
                ],
                'is_need_deep_inspection' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'Нужна ли глубокая инспекция',
                    'aliases' => ['deep_inspection', 'глубокая_инспекция'],
                    'default' => 0
                ],
                'is_deleted' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Флаг удаления',
                    'aliases' => ['deleted', 'удален'],
                    'default' => 0
                ],
                'link_tz' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Ссылка на ТЗ',
                    'aliases' => ['tz', 'тз']
                ],
                'product_name_en' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Название продукта на английском',
                    'aliases' => ['name_en', 'english_name']
                ],
                'product_description_en' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Описание продукта на английском',
                    'aliases' => ['description_en', 'english_description']
                ],
                'product_name_zh' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Название продукта на китайском',
                    'aliases' => ['name_zh', 'chinese_name']
                ],
                'product_description_zh' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Описание продукта на китайском',
                    'aliases' => ['description_zh', 'chinese_description']
                ],
                'currency' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Валюта',
                    'aliases' => ['currency_code', 'валюта'],
                    'default' => 'RUB'
                ],
                'amount_of_space' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Количество места',
                    'aliases' => ['space', 'место']
                ],
                'cargo_number' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Номер груза',
                    'aliases' => ['cargo', 'груз']
                ],
                'waybill_isset' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Наличие накладной',
                    'aliases' => ['waybill', 'накладная'],
                    'default' => 0
                ],
                'client_waybill_isset' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Наличие клиентской накладной',
                    'aliases' => ['client_waybill', 'клиентская_накладная'],
                    'default' => 0
                ],
                'delivery_start_date' => [
                    'type' => 'datetime',
                    'required' => false,
                    'description' => 'Дата начала доставки',
                    'aliases' => ['start_date', 'дата_начала']
                ],
                'delivery_days_expected' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Ожидаемые дни доставки',
                    'aliases' => ['expected_days', 'ожидаемые_дни'],
                    'default' => 0
                ],
                'delivery_delay_days' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Дни задержки доставки',
                    'aliases' => ['delay_days', 'дни_задержки'],
                    'default' => 0
                ]
            ]
        )
    ],
    'Product' => [
        'fields' => array_merge(
            [
                'id' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'ID товара',
                    'aliases' => ['id', 'ID']
                ]
            ],
            $multiLangFields(),
            [
                'rating' => [
                    'type' => 'float',
                    'required' => false,
                    'description' => 'Рейтинг товара',
                    'aliases' => ['rating', 'рейтинг'],
                    'default' => 0.00
                ],
                'feedback_count' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Количество отзывов',
                    'aliases' => ['feedback', 'отзывы', 'количество_отзывов'],
                    'default' => 0
                ],
                'buyer_id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID покупателя',
                    'aliases' => ['buyer', 'покупатель', 'Покупатель']
                ],
                'subcategory_id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID подкатегории',
                    'aliases' => ['category', 'категория', 'Категория']
                ],
                'range_1_min' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'Минимальное количество (диапазон 1)',
                    'aliases' => ['min_quantity', 'мин_количество', 'Мин_количество'],
                    'default' => 1
                ],
                'range_1_max' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'Максимальное количество (диапазон 1)',
                    'aliases' => ['max_quantity', 'макс_количество', 'Макс_количество']
                ],
                'range_1_price' => [
                    'type' => 'float',
                    'required' => true,
                    'description' => 'Цена за единицу (диапазон 1)',
                    'aliases' => ['price', 'цена', 'Цена']
                ],
                'range_2_min' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Минимальное количество (диапазон 2)',
                    'aliases' => ['min_quantity_2', 'мин_количество_2']
                ],
                'range_2_max' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Максимальное количество (диапазон 2)',
                    'aliases' => ['max_quantity_2', 'макс_количество_2']
                ],
                'range_2_price' => [
                    'type' => 'float',
                    'required' => false,
                    'description' => 'Цена за единицу (диапазон 2)',
                    'aliases' => ['price_2', 'цена_2']
                ],
                'range_3_min' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Минимальное количество (диапазон 3)',
                    'aliases' => ['min_quantity_3', 'мин_количество_3']
                ],
                'range_3_max' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Максимальное количество (диапазон 3)',
                    'aliases' => ['max_quantity_3', 'макс_количество_3']
                ],
                'range_3_price' => [
                    'type' => 'float',
                    'required' => false,
                    'description' => 'Цена за единицу (диапазон 3)',
                    'aliases' => ['price_3', 'цена_3']
                ],
                'range_4_min' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Минимальное количество (диапазон 4)',
                    'aliases' => ['min_quantity_4', 'мин_количество_4']
                ],
                'range_4_max' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Максимальное количество (диапазон 4)',
                    'aliases' => ['max_quantity_4', 'макс_количество_4']
                ],
                'range_4_price' => [
                    'type' => 'float',
                    'required' => false,
                    'description' => 'Цена за единицу (диапазон 4)',
                    'aliases' => ['price_4', 'цена_4']
                ],
                'is_deleted' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Флаг удаления',
                    'aliases' => ['deleted', 'удален'],
                    'default' => 0
                ],
                'product_height' => [
                    'type' => 'float',
                    'required' => false,
                    'description' => 'Высота товара',
                    'aliases' => ['height', 'высота'],
                    'default' => 0
                ],
                'product_width' => [
                    'type' => 'float',
                    'required' => false,
                    'description' => 'Ширина товара',
                    'aliases' => ['width', 'ширина'],
                    'default' => 0
                ],
                'product_depth' => [
                    'type' => 'float',
                    'required' => false,
                    'description' => 'Глубина товара',
                    'aliases' => ['depth', 'глубина'],
                    'default' => 0
                ],
                'product_weight' => [
                    'type' => 'float',
                    'required' => false,
                    'description' => 'Вес товара',
                    'aliases' => ['weight', 'вес'],
                    'default' => 0
                ],
                'currency' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Валюта',
                    'aliases' => ['currency_code', 'валюта'],
                    'default' => 'RUB'
                ]
            ]
        )
    ]
];