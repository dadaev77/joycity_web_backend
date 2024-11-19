<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\Category;

class CategoryOutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = Category::find()
            ->where([
                'id' => $ids,
            ])
            ->with(['avatar', 'subcategories']);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);

            unset(
                $info['avatar_id'],
                $info['is_deleted'],
                $info['subcategories']['is_deleted'],
            );

            foreach ($info['subcategories'] as &$subcategories) {
                unset(
                    $subcategories['is_deleted'],
                    $subcategories['category_id'],
                );
            }

            return $info;
        }, $query->all());
    }
}
