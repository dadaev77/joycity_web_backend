<?php

namespace app\services\modificators;

use app\models\Rate;

use app\services\RateService as BaseRateService;

class RateService extends BaseRateService
{
    /*
     * Методы родительского класса:
     * - convertCNYtoRUB
     * - outputInUserCurrency
     * - putInUserCurrency
     * - convertToInitialCurrency
     * - convertUSDtoCNY
     * - convertCNYtoUSD
     * - convertUSDtoRUB
     * - convertRUBtoCNY
     * - convertRUBtoUSD
     * - convertCrossRate
     * - convertSimpleRate
     * - getRate
     * - getOrderRate
     * - setSADP
     */

    /**
     * Get the current rates from the database.
     *
     * @return array An associative array containing the current rates for USD, CNY, and RUB.
     * @throws \Exception If current rates are not found in the database.
     */
    private static function getCurrentRates(): array
    {
        $rate = Rate::find()->orderBy(['id' => SORT_DESC])->one();
        if (!$rate) {
            throw new \Exception("Current rates not found.");
        }

        return [
            'USD' => $rate->USD ?? 0,
            'CNY' => $rate->CNY ?? 0,
            'RUB' => 1, // RUB is the base currency
        ];
    }

    /**
     * Convert an amount to the initial currency (RUB).
     *
     * @param float $amount The amount to convert.
     * @param string $currency The currency to convert from.
     * @return float The converted amount in RUB.
     */
    public static function convertToInitial(float $amount, string $currency): float
    {
        $currency = strtoupper($currency);
        $rates = self::getCurrentRates();
        return round($amount * $rates[$currency], self::$SYMBOLS_AFTER_DECIMAL_POINT);
    }

    /**
     * Convert an amount from RUB to USD.
     *
     * @param float $amount The amount in RUB to convert.
     * @return float The equivalent amount in USD.
     */
    public static function convertFromRubToUsd(float $amount): float
    {
        $rates = self::getCurrentRates();
        return round($amount / $rates['USD'], self::$SYMBOLS_AFTER_DECIMAL_POINT);
    }

    /**
     * Convert an amount from RUB to CNY.
     *
     * @param float $amount The amount in RUB to convert.
     * @return float The equivalent amount in CNY.
     */
    public static function convertFromRubToCny(float $amount): float
    {
        $rates = self::getCurrentRates();
        return round($amount / $rates['CNY'], self::$SYMBOLS_AFTER_DECIMAL_POINT);
    }

    /**
     * Convert an amount from CNY to RUB.
     *
     * @param float $amount The amount in CNY to convert.
     * @return float The equivalent amount in RUB.
     */
    public static function convertFromCnyToRub(float $amount): float
    {
        $rates = self::getCurrentRates();
        return round($amount * $rates['CNY'], self::$SYMBOLS_AFTER_DECIMAL_POINT);
    }

    /**
     * Convert an amount from USD to RUB.
     *
     * @param float $amount The amount in USD to convert.
     * @return float The equivalent amount in RUB.
     */
    public static function convertFromUsdToRub(float $amount): float
    {
        $rates = self::getCurrentRates();
        return round($amount * $rates['USD'], self::$SYMBOLS_AFTER_DECIMAL_POINT);
    }

    /**
     * Convert an amount from CNY to USD.
     *
     * @param float $amount The amount in CNY to convert.
     * @return float The equivalent amount in USD.
     */
    public static function convertFromCnyToUsd(float $amount): float
    {
        $rates = self::getCurrentRates();
        return round($amount * ($rates['USD'] / $rates['CNY']), self::$SYMBOLS_AFTER_DECIMAL_POINT);
    }

    /**
     * Convert an amount from USD to CNY.
     *
     * @param float $amount The amount in USD to convert.
     * @return float The equivalent amount in CNY.
     */
    public static function convertFromUsdToCny(float $amount): float
    {
        $rates = self::getCurrentRates();
        return round($amount * ($rates['CNY'] / $rates['USD']), self::$SYMBOLS_AFTER_DECIMAL_POINT);
    }

    /**
     * Get an amount in the user's currency.
     * If user is not logged in, convert to initial currency.
     * @param float $amount The amount to convert.
     * @param string $currency The currency to convert from.
     * @return float The converted amount in the user's currency.
     */
    public static function getInUserCurrency(float $amount, string $currency): float
    {
        $currency = strtoupper($currency);
        $user = User::getIdentity();
        $rates = self::getCurrentRates();

        if (!$user) return self::convertToInitial($amount, $currency);

        $userCurrency = $user->userSettings->currency;
        return round($amount * $rates[$userCurrency] / $rates[$currency], self::$SYMBOLS_AFTER_DECIMAL_POINT);
    }
}
