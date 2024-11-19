<?php

namespace app\services;

use app\models\Order;

class ProductSortService
{
    //для сортировки по ценам;
    public static function compareForSorting($productA, $productB, $sort)
    {
        $minPriceA = $productA['price']['min_price'];
        $minPriceB = $productB['price']['min_price'];

        if ($sort === 'asc') {
            if ($minPriceA !== null && $minPriceB !== null) {
                return $minPriceA <=> $minPriceB;
            } elseif ($minPriceA !== null) {
                return 1;
            } elseif ($minPriceB !== null) {
                return -1;
            }
        } elseif ($sort === 'desc') {
            $maxPriceA = $productA['price']['max_price'];
            $maxPriceB = $productB['price']['max_price'];

            if ($maxPriceA !== null && $maxPriceB !== null) {
                return $maxPriceB <=> $maxPriceA;
            } elseif ($maxPriceA !== null) {
                return 1;
            } elseif ($maxPriceB !== null) {
                return -1;
            }
        }

        return 0;
    }

    //сортировка по кол-ву в o_r
    public static function sortProductsByOrder($products)
    {
        $popularProducts = Order::find()
            ->select(['product_id', 'COUNT(*) as order_count'])
            ->where(['<>', 'product_id', 0])
            ->groupBy('product_id')
            ->orderBy('order_count DESC')
            ->asArray()
            ->all();

        return ProductSortService::sortProductsByOrderCount(
            $products,
            $popularProducts
        );
    }

    public static function sortProductsByOrderCount($products, $popularProducts)
    {
        usort($products, function ($a, $b) use ($popularProducts) {
            $orderCountA = self::getOrderCountForProduct(
                $popularProducts,
                $a['id']
            );
            $orderCountB = self::getOrderCountForProduct(
                $popularProducts,
                $b['id']
            );
            return $orderCountB - $orderCountA;
        });

        return $products;
    }

    public static function getOrderCountForProduct($popularProducts, $productId)
    {
        foreach ($popularProducts as $popularProduct) {
            if ($popularProduct['product_id'] == $productId) {
                return $popularProduct['order_count'];
            }
        }

        return 0;
    }
}
