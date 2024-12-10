<?php

namespace app\services;

use app\models\OrderRate;
use app\models\Rate;
use app\models\User;
use Throwable;

class RateService
{
    public const CURRENCY_RUB = 'RUB';
    public const CURRENCY_CNY = 'CNY';
    public const CURRENCY_USD = 'USD';

    public static int $SYMBOLS_AFTER_DECIMAL_POINT = 0;

    protected static array $currentRate;
    protected static array $orderRates = [];

    public static function setSADP(int $symbolsAfterDecimalPoint): void
    {
        self::$SYMBOLS_AFTER_DECIMAL_POINT = $symbolsAfterDecimalPoint;
    }

    // Get the latest currency rate
    public static function getRate(): array
    {
        if (!empty(self::$currentRate))
            return self::$currentRate;
        return self::$currentRate = Rate::find()->orderBy(['id' => SORT_DESC])->asArray()->one();
    }

    // Get the order rate for a specific order
    protected static function getOrderRate(int $orderId): array
    {
        if (!empty(self::$orderRates[$orderId])) return self::$orderRates[$orderId];
        $orderRate = OrderRate::find()->asArray()->where(['order_id' => $orderId])->one();
        if (!$orderRate) return self::getRate();
        self::$orderRates[$orderId] = $orderRate;
        return self::$orderRates[$orderId];
    }

    // Метод для конвертации отдельного значения
    public static function convertValue(float $value, string $fromCurrency, string $toCurrency): float
    {
        if ($value == 0) {
            return 0;
        }

        $rate = self::getRate();
        if ($fromCurrency === $toCurrency) {
            return $value;
        }
        if ($fromCurrency !== self::CURRENCY_RUB) {
            $value = $value * $rate[$fromCurrency];
        }
        if ($toCurrency !== self::CURRENCY_RUB) {
            $value = $value / $rate[$toCurrency];
        }
        return round($value, self::$SYMBOLS_AFTER_DECIMAL_POINT);
    }

    // Метод для конвертации массива цен
    public static function convertPrices(array $prices, string $fromCurrency, string $toCurrency): array
    {
        foreach ($prices as &$price) {
            if (is_numeric($price)) {
                $price = self::convertValue($price, $fromCurrency, $toCurrency);
            }
        }
        return $prices;
    }

    // Метод для конвертации цен в массиве данных
    public static function convertDataPrices(array $data, array $priceKeys, string $fromCurrency, string $toCurrency): array
    {
        foreach ($priceKeys as $key) {
            if (isset($data[$key])) {
                if (is_array($data[$key])) {
                    $data[$key] = self::convertPrices($data[$key], $fromCurrency, $toCurrency);
                } elseif (is_numeric($data[$key])) {
                    $data[$key] = self::convertValue($data[$key], $fromCurrency, $toCurrency);
                }
            }
        }
        return $data;
    }
}
