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

    public const SYMBOLS_AFTER_DECIMAL_POINT = 4;

    protected static array $currentRate;
    protected static array $orderRates = [];

    // Get the latest currency rate
    protected static function getRate(): array
    {
        if (!empty(self::$currentRate)) return self::$currentRate;

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

    // Output amount in user's currency
    public static function outputInUserCurrency(float $amount, int $orderId = 0): float
    {
        $userCurrency = User::getIdentity()->userSettings->currency;
        return $userCurrency === self::CURRENCY_RUB ? round($amount, self::SYMBOLS_AFTER_DECIMAL_POINT) : self::convertRUBtoCNY($amount, $orderId, self::SYMBOLS_AFTER_DECIMAL_POINT);
    }

    // Convert amount to user's currency
    public static function putInUserCurrency(float $amount, int $orderId = 0): float
    {
        $userCurrency = User::getIdentity()->userSettings->currency;

        return $userCurrency === self::CURRENCY_RUB ? round($amount, self::SYMBOLS_AFTER_DECIMAL_POINT) : self::convertRUBtoCNY($amount, $orderId, self::SYMBOLS_AFTER_DECIMAL_POINT);
    }

    // Convert amount from a specific currency to the initial currency
    public static function convertToInitialCurrency(string $fromCurrency, float $amount, int $orderId = 0)
    {
        return self::convertSimpleRate($fromCurrency, self::CURRENCY_RUB, $amount, self::SYMBOLS_AFTER_DECIMAL_POINT, $orderId);
    }

    // Convert amount from CNY to RUB
    public static function convertCNYtoRUB(float $amount, int $orderId = 0): float
    {
        return self::convertSimpleRate(self::CURRENCY_CNY, self::CURRENCY_RUB, $amount, self::SYMBOLS_AFTER_DECIMAL_POINT, $orderId);
    }

    // Convert amount from RUB to CNY
    public static function convertRUBtoCNY(float $amount, int $orderId = 0): float
    {
        return self::convertSimpleRate(self::CURRENCY_RUB, self::CURRENCY_CNY, $amount, self::SYMBOLS_AFTER_DECIMAL_POINT, $orderId);
    }

    // Convert amount from USD to CNY
    public static function convertUSDtoCNY(float $amount, int $orderId = 0): float
    {
        return self::convertSimpleRate(self::CURRENCY_USD, self::CURRENCY_CNY, $amount, self::SYMBOLS_AFTER_DECIMAL_POINT, $orderId);
    }

    // Convert amount from CNY to USD
    public static function convertCNYtoUSD(float $amount, int $orderId = 0): float
    {
        return self::convertSimpleRate(self::CURRENCY_CNY, self::CURRENCY_USD, $amount, self::SYMBOLS_AFTER_DECIMAL_POINT, $orderId);
    }

    // Convert amount from USD to RUB
    public static function convertUSDtoRUB(float $amount, int $orderId = 0): float
    {
        return self::convertCrossRate(self::CURRENCY_USD, self::CURRENCY_RUB, $amount, self::SYMBOLS_AFTER_DECIMAL_POINT, $orderId);
    }

    // Convert amount from RUB to USD
    public static function convertRUBtoUSD(float $amount, int $orderId = 0): float
    {
        return self::convertCrossRate(self::CURRENCY_RUB, self::CURRENCY_USD, $amount, self::SYMBOLS_AFTER_DECIMAL_POINT, $orderId);
    }

    // Convert amount using a simple rate conversion
    private static function convertSimpleRate(string $fromCurrency, string $toCurrency, float $amount, int $orderId = 0): float
    {
        $currentRate = $orderId ? self::getOrderRate($orderId) : self::getRate();
        return round($amount * ($currentRate[$fromCurrency] / $currentRate[$toCurrency]), self::SYMBOLS_AFTER_DECIMAL_POINT);
    }

    // Convert amount using a cross rate conversion
    private static function convertCrossRate(string $fromCurrency, string $toCurrency, float $amount, int $orderId = 0): float
    {
        $amountInBaseCurrency = self::convertSimpleRate($fromCurrency, self::CURRENCY_CNY, $amount, $orderId);
        return self::convertSimpleRate(self::CURRENCY_CNY, $toCurrency, $amountInBaseCurrency, $orderId);
    }
}
