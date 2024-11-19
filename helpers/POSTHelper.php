<?php

namespace app\helpers;

use Yii;

class POSTHelper
{
    public static function getPostWithKeys(array $keys, bool $fillNull = false)
    {
        $request = Yii::$app->request;

        if ($fillNull) {
            $array = array_fill_keys($keys, null);

            return array_intersect_key($request->post(), $array) + $array;
        }

        return array_intersect_key($request->post(), array_flip($keys));
    }

    public static function getEmptyParams(
        array $params,
        bool $keysOnly = false,
    ): array {
        $emptyParams = array_filter($params, static function ($value) {
            return empty($value);
        });

        return $keysOnly ? array_keys($emptyParams) : $emptyParams;
    }
}
