<?php

namespace app\services;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\models\Product;

class ProductFilterService
{
    public function actionSearchProducts()
    {
        $offset = Yii::$app->request->get('offset', 0);
        $subcategoryId = Yii::$app->request->get('subcategory_id');
        $productName = Yii::$app->request->get('product_name');
        $priceMin = Yii::$app->request->get('price_min');
        $priceMax = Yii::$app->request->get('price_max');

        $query = Product::find()
            ->joinWith('productAttachments')
            ->where(['subcategory_id' => $subcategoryId]);

        if (!empty($productName)) {
            $query->andWhere(['like', 'name', $productName]);
        }

        if (!empty($priceMin) && !empty($priceMax)) {
            $query->andWhere([
                'or',
                ['between', 'lot_size_price_level_one', $priceMin, $priceMax],
                ['between', 'lot_size_price_level_two', $priceMin, $priceMax],
                ['between', 'lot_size_price_level_three', $priceMin, $priceMax],
                ['between', 'lot_size_price_level_four', $priceMin, $priceMax],
            ]);
        }

        $products = $query
            ->asArray()
            ->offset($offset)
            ->limit(20)
            ->all();

        if (empty($products)) {
            return ApiResponse::byResponseCode(
                ResponseCodes::getSelf()->NOT_FOUND,
                ['message' => 'Продукты не найдены']
            );
        }

        return ApiResponse::byResponseCode(null, ['products' => $products]);
    }
}
