<?php

namespace app\services;

use app\models\TypeDelivery;
use app\models\TypeDeliveryLinkCategory;

use Throwable;
use app\models\Category;

class TypeDeliveryService
{
    public static function getTypeDeliveryIdsBySubcategory(int $subcategoryId): mixed //array
    {

        try {
            $typeDeliveryIds = TypeDeliveryLinkCategory::find()
                ->select(['type_delivery_id'])
                ->where(['category_id' => $subcategoryId])
                ->column();

            if (!$typeDeliveryIds) {
                // get parent category
                $category = Category::findOne(['id' => $subcategoryId]);

                $parentCategory = Category::findOne([
                    'id' => $category->parent_id,
                ]);

                $typeDeliveryIds = TypeDeliveryLinkCategory::find()
                    ->select(['type_delivery_id'])
                    ->where(['category_id' => $parentCategory->id])
                    ->column();
            }

            if (!$typeDeliveryIds) {
                $typeDeliveryIds = TypeDelivery::find()
                    ->where(['available_for_all' => 1])
                    ->column();
            }
            return $typeDeliveryIds;
        } catch (Throwable) {
            return [];
        }
    }
}
