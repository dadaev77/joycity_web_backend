<?php

namespace app\helpers;

use app\models\Base;
use yii\db\ActiveRecord;

class ModelResponseHelper
{
    public static function filterModelFields(
        Base|ActiveRecord $model,
        array $fields = [],
        array $forceInclude = []
    ): array {
        $info = ModelTypeHelper::toArray($model);
        $out = [];

        foreach ($info as $key => $value) {
            if (
                in_array($key, $fields, true) ||
                in_array($key, $forceInclude, true)
            ) {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}
