<?php

use app\models\Order;


// Функции валидации для разных типов данных
$validators = [
    'delivery_type' => function($value) {
        if (empty($value)) {
            return null;
        }
        $value = trim($value);
        if (is_numeric($value)) {
            return (int)$value;
        }
        $types = [
            'Медленное авто' => 8,
            'медленное авто' => 8,
            'Быстрое авто' => 9,
            'быстрое авто' => 9,
            'Авиа' => 3,
            'авиа' => 3,
            'Морем' => 4,
            'морем' => 4,
            'Железная дорога' => 5,
            'железная дорога' => 5,
            '8' => 8,
            '9' => 9
        ];
        return $types[$value] ?? null;
    },
    
    'delivery_point' => function($value) {
        if (empty($value)) {
            return null;
        }
        $value = trim($value);
        if (is_numeric($value)) {
            $id = (int)$value;
            if ($id >= 1 && $id <= 4) {
                return $id;
            }
            return null;
        }
        $types = [
            'фулфилмент' => 1,
            'Фулфилмент' => 1,
            'ФУЛФИЛМЕНТ' => 1,
            'склад' => 2,
            'Склад' => 2,
            'СКЛАД' => 2,
            'магазин' => 3,
            'Магазин' => 3,
            'МАГАЗИН' => 3,
            'пункт выдачи' => 4,
            'Пункт выдачи' => 4,
            'ПУНКТ ВЫДАЧИ' => 4,
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4
        ];
        return $types[mb_strtolower(trim($value), 'UTF-8')] ?? null;
    },
    
    'delivery_address' => function($value) {
        if (empty($value)) {
            return null;
        }
        $value = trim($value);
        if (is_numeric($value)) {
            $id = (int)$value;
            if ($id === 1 || $id === 2) {
                return $id;
            }
            return null;
        }
        $addresses = [
            // ID 1
            'москва, тестовый склад' => 1,
            'Москва, Тестовый склад' => 1,
            'МОСКВА, ТЕСТОВЫЙ СКЛАД' => 1,
            'москва тестовый склад' => 1,
            'Москва Тестовый склад' => 1,
            'тестовый склад' => 1,
            'Тестовый склад' => 1,
            
            // ID 2
            'кутузовское ш 12' => 2,
            'Кутузовское ш 12' => 2,
            'КУТУЗОВСКОЕ Ш 12' => 2,
            'кутузовское шоссе 12' => 2,
            'Кутузовское шоссе 12' => 2,
            'кутузовское ш. 12' => 2,
            'Кутузовское ш. 12' => 2,
            'кутузовское' => 2,
            'Кутузовское' => 2
        ];
        
        $normalizedAddress = mb_strtolower(trim($value), 'UTF-8');
        $normalizedAddress = preg_replace('/\s+/', ' ', $normalizedAddress);
        
        // Прямое совпадение
        if (isset($addresses[$normalizedAddress])) {
            return $addresses[$normalizedAddress];
        }
        
        // Частичное совпадение
        if (strpos($normalizedAddress, 'москва') !== false && 
            strpos($normalizedAddress, 'тестовый') !== false && 
            strpos($normalizedAddress, 'склад') !== false) {
            return 1;
        }
        
        if (strpos($normalizedAddress, 'кутузовск') !== false && 
            (strpos($normalizedAddress, '12') !== false || 
             strpos($normalizedAddress, 'двенадцать') !== false)) {
            return 2;
        }
        
        return null;
    },
    
    'packaging_type' => function($value) {
        if (empty($value)) {
            return null;
        }
        $types = [
            'Мешок + скотч' => 1,
            'Коробка' => 2,
            'Пакет' => 3,
            'Пленка' => 4,
            'Упаковка' => 5,
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '5' => 5
        ];
        return $types[trim($value)] ?? null;
    }
];

return [
    'Order' => [
        'validators' => $validators,
        'required_fields' => [
            'product_name_ru' => 'Название товара обязательно для заполнения',
            'expected_quantity' => 'Количество товара должно быть больше 0',
            'expected_price_per_item' => 'Цена за единицу товара должна быть больше или равна 0',
            'subcategory_id' => 'Необходимо указать подкатегорию товара',
            'type_delivery_id' => 'Необходимо указать тип доставки',
            'type_delivery_point_id' => 'Необходимо указать тип пункта доставки',
            'delivery_point_address_id' => 'Необходимо указать адрес пункта доставки',
            'type_packaging_id' => 'Необходимо указать тип упаковки',
            'expected_packaging_quantity' => 'Количество упаковок должно быть больше 0'
        ],
        'numeric_constraints' => [
            'expected_quantity' => ['min' => 1],
            'expected_price_per_item' => ['min' => 0],
            'expected_packaging_quantity' => ['min' => 1]
        ]
    ]
];
