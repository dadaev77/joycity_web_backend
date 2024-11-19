<?php

namespace app\helpers;

class ArrayHelperExtended
{
    public static function mapDeep(callable $cb, array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::mapDeep($cb, $value);
            } else {
                $array[$key] = $cb($value);
            }
        }

        return $array;
    }
}
