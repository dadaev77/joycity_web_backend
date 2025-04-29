<?php

namespace app\services;

class PriceService
{
    public static function calculateMinMaxPrice($products)
    {
        foreach ($products as &$product) {
            $minPrice = min(
                $product['range_1_price'],
                $product['range_2_price'] ?? $product['range_1_price'],
                $product['range_3_price'] ?? $product['range_1_price'],
                $product['range_4_price'] ?? $product['range_1_price']
            );

            $maxPrice = max(
                $product['range_1_price'],
                $product['range_2_price'] ?? $product['range_1_price'],
                $product['range_3_price'] ?? $product['range_1_price'],
                $product['range_4_price'] ?? $product['range_1_price']
            );

            $product['price'] = [
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
            ];
        }

        return $products;
    }
}
