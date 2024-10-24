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
            $typeDeliveryIds = [];
            $currentCategory = Category::findOne(['id' => $subcategoryId]);
            if (!$currentCategory) return [];

            $categoriesTree = [];
            $categoriesTree[] = $currentCategory;
            while ($currentCategory->parent_id) {
                $currentCategory = Category::findOne(['id' => $currentCategory->parent_id]);
                if ($currentCategory) {
                    $categoriesTree[] = $currentCategory;
                }
            }
            array_reverse($categoriesTree);

            foreach ($categoriesTree as $category) {
                $typeDeliveryIds = TypeDeliveryLinkCategory::find()
                    ->select(['type_delivery_id'])
                    ->where(['category_id' => $category->id])
                    ->column();
                if ($typeDeliveryIds) {
                    $typeDeliveryIds = $typeDeliveryIds;
                    break;
                }
            }

            // get type delivery that available for all
            if (!$typeDeliveryIds) {
                $typeDeliveryIds = TypeDelivery::find()
                    ->where(['available_for_all' => 1])
                    ->column();
            }
            // return type delivery ids

            return $typeDeliveryIds;
        } catch (Throwable) {
            return [];
        }
    }
}
