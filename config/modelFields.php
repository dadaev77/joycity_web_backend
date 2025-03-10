<?php

use app\models\Order;


// Функции валидации для разных типов данных
$validators = [
    'integer' => function($value, $config) {
        if (!is_numeric($value) || !is_int($value + 0)) {
            return "Поле '{$config['description']}' должно быть целым числом";
        }
        if (isset($config['min']) && $value < $config['min']) {
            return "Значение поля '{$config['description']}' должно быть не меньше {$config['min']}";
        }
        if (isset($config['max']) && $value > $config['max']) {
            return "Значение поля '{$config['description']}' должно быть не больше {$config['max']}";
        }
        if (isset($config['values']) && !in_array($value, $config['values'])) {
            return "Недопустимое значение для поля '{$config['description']}'";
        }
        return null;
    },
    'float' => function($value, $config) {
        if (!is_numeric($value)) {
            return "Поле '{$config['description']}' должно быть числом";
        }
        if (isset($config['min']) && $value < $config['min']) {
            return "Значение поля '{$config['description']}' должно быть не меньше {$config['min']}";
        }
        if (isset($config['max']) && $value > $config['max']) {
            return "Значение поля '{$config['description']}' должно быть не больше {$config['max']}";
        }
        return null;
    },
    'string' => function($value, $config) {
        if (isset($config['min_length']) && mb_strlen($value) < $config['min_length']) {
            return "Длина поля '{$config['description']}' должна быть не менее {$config['min_length']} символов";
        }
        return null;
    }
];

return [
    'Order' => [
        'fields' => [
            'id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'ID заявки',
                'aliases' => ['id', 'order_id'],
                'validate' => $validators['integer']
            ],
            'product_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'ID продукта',
                'aliases' => ['product_id', 'товар'],
                'validate' => $validators['integer']
            ],
            'client_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'ID клиента',
                'aliases' => ['client_id', 'клиент'],
                'validate' => $validators['integer']
            ],
            'status' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Статус заявки',
                'aliases' => ['status', 'статус'],
                'values' => [
                    Order::STATUS_CREATED,
                    Order::STATUS_BUYER_ASSIGNED,
                    Order::STATUS_BUYER_OFFER_CREATED,
                    Order::STATUS_BUYER_OFFER_ACCEPTED,
                    Order::STATUS_PAID,
                    Order::STATUS_CANCELLED_REQUEST
                ],
                'validate' => $validators['string']
            ],
            'expected_quantity' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Желаемое количество товара',
                'aliases' => ['количество', 'quantity'],
                'help' => '(от 1 до 100 000)',
                'min' => 1,
                'max' => 100000,
                'validate' => $validators['integer']
            ],
            'expected_price_per_item' => [
                'type' => 'float',
                'required' => true,
                'description' => 'Желаемая стоимость за единицу',
                'aliases' => ['цена', 'price'],
                'help' => '(от 1 до 1 000 000)',
                'min' => 1,
                'max' => 1000000,
                'validate' => $validators['float']
            ],
            'type_delivery_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Тип доставки',
                'aliases' => ['доставка', 'delivery'],
                'help' => 'Выберите из списка',
                'validate' => $validators['integer']
            ],
            'type_delivery_point_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Тип пункта доставки',
                'aliases' => ['пункт_доставки', 'delivery_point'],
                'help' => 'Выберите из списка',
                'validate' => $validators['integer']
            ],
            'delivery_point_address_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Адрес пункта доставки',
                'aliases' => ['адрес', 'address'],
                'help' => 'Выберите из списка',
                'validate' => $validators['integer']
            ],
            'type_packaging_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Тип упаковки',
                'aliases' => ['упаковка', 'packaging'],
                'help' => 'Выберите из списка',
                'validate' => $validators['integer']
            ],
            'expected_packaging_quantity' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Количество упаковок',
                'aliases' => ['количество_упаковок', 'packaging_quantity'],
                'help' => '(от 1 до 100 000)',
                'min' => 1,
                'max' => 100000,
                'validate' => $validators['integer']
            ],
            'is_need_deep_inspection' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Глубокая инспекция',
                'aliases' => ['инспекция', 'inspection'],
                'help' => 'Выберите из списка',
                'values' => [0, 1],
                'validate' => $validators['integer']
            ],
            'created_at' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Дата создания',
                'aliases' => ['created_at', 'дата_создания'],
                'validate' => $validators['string']
            ]
        ]
    ],
    'Product' => [
        'fields' => [
            'images' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Фотографии товара',
                'aliases' => ['фото', 'photos', 'images'],
                'help' => '(Добавьте сюда фотографии вашего товара)',
                'validate' => $validators['string']
            ],
            'name_ru' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Название товара',
                'aliases' => ['название', 'name', 'наименование'],
                'help' => '(Например Брюки женские)',
                'example' => 'Брюки женские',
                'validate' => $validators['string']
            ],
            'category_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Категория товара',
                'aliases' => ['категория', 'category'],
                'help' => 'Выберите из списка',
                'validate' => $validators['integer']
            ],
            'subcategory_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Подкатегория',
                'aliases' => ['подкатегория', 'subcategory'],
                'help' => 'Выберите из списка',
                'validate' => $validators['integer']
            ],
            'description_ru' => [
                'type' => 'string',
                'required' => true,
                'description' => 'Описание товара',
                'aliases' => ['описание', 'description'],
                'help' => '(Добавьте описание товара минимум 20 символов)',
                'min_length' => 20,
                'validate' => $validators['string']
            ],
            'expected_quantity' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Желаемое количество товара, шт',
                'aliases' => ['количество', 'quantity'],
                'help' => '(от 1 до 100 000)',
                'min' => 1,
                'max' => 100000,
                'example' => '1000',
                'validate' => $validators['integer']
            ],
            'expected_price_per_item' => [
                'type' => 'float',
                'required' => true,
                'description' => 'Желаемая стоимость за единицу товара, Р',
                'aliases' => ['цена', 'price'],
                'help' => '(от 1 до 1 000 000)',
                'min' => 1,
                'max' => 1000000,
                'example' => '1000',
                'validate' => $validators['float']
            ],
            'type_delivery_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Тип доставки',
                'aliases' => ['доставка', 'delivery'],
                'help' => 'Выберите из списка',
                'validate' => function($value, $config) use ($validators) {
                    $error = $validators['integer']($value, $config);
                    if ($error !== null) {
                        return $error;
                    }
                    // Проверка существования в справочнике
                    if (!TypeDelivery::findOne($value)) {
                        return "Выбранный тип доставки не существует";
                    }
                    return null;
                }
            ],
            'type_delivery_point_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Тип пункта доставки',
                'aliases' => ['пункт_доставки', 'delivery_point'],
                'help' => 'Выберите из списка',
                'validate' => $validators['integer']
            ],
            'delivery_point_address_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Адрес пункта доставки',
                'aliases' => ['адрес', 'address'],
                'help' => 'Выберите из списка',
                'validate' => $validators['integer']
            ],
            'type_packaging_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Тип упаковки',
                'aliases' => ['упаковка', 'packaging'],
                'help' => 'Выберите из списка',
                'validate' => $validators['integer']
            ],
            'expected_packaging_quantity' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Количество упаковок, шт',
                'aliases' => ['количество_упаковок', 'packaging_quantity'],
                'help' => '(от 1 до 100 000)',
                'min' => 1,
                'max' => 100000,
                'example' => '100',
                'validate' => $validators['integer']
            ],
            'is_need_deep_inspection' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'Глубокая инспекция',
                'aliases' => ['инспекция', 'inspection'],
                'help' => 'Выберите из списка',
                'values' => [0, 1],
                'validate' => $validators['integer']
            ]
        ]
    ]
];
