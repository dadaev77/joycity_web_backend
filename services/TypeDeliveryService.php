<?php

namespace app\services;

use app\models\Subcategory;
use app\models\TypeDelivery;
use app\models\TypeDeliveryLinkCategory;
use app\models\TypeDeliveryLinkSubcategory;
use Throwable;

class TypeDeliveryService
{
    public static function getTypeDeliveryIdsBySubcategory(
        int $subcategoryId,
    ): array {
        try {
            $typeDeliveryIds = TypeDeliveryLinkSubcategory::find()
                ->select(['type_delivery_id'])
                ->where(['subcategory_id' => $subcategoryId])
                ->column();

            if (!$typeDeliveryIds) {
                $categoryId = Subcategory::findOne(['id' => $subcategoryId])
                    ?->category_id;

                if ($categoryId) {
                    $typeDeliveryIds = TypeDeliveryLinkCategory::find()
                        ->select(['type_delivery_id'])
                        ->where(['category_id' => $categoryId])
                        ->column();
                }

                if (!$typeDeliveryIds) {
                    $typeDeliveryIds = TypeDelivery::find()
                        ->where(['available_for_all' => 1])
                        ->column();
                }
            }

            return $typeDeliveryIds;
        } catch (Throwable) {
            return [];
        }
    }
}
