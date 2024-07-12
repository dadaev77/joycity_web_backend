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

    protected static array $currentRate;
    protected static array $orderRates = [];

    protected static function getRate(): array
    {
        if (!empty(self::$currentRate)) {
            return self::$currentRate;
        }

        return self::$currentRate = Rate::find()
            ->orderBy(['id' => SORT_DESC])
            ->asArray()
            ->one();
    }

    protected static function getOrderRate(int $orderId): array
    {
        if (!empty(self::$orderRates[$orderId])) {
            return self::$orderRates[$orderId];
        }

        $orderRate = OrderRate::find()
            ->asArray()
            ->where(['order_id' => $orderId])
            ->one();

        if (!$orderRate) {
            return self::getRate();
        }

        self::$orderRates[$orderId] = $orderRate;

        return self::$orderRates[$orderId];
    }

    public static function outputInUserCurrency(
        float $amount,
        int $orderId = 0,
        int $precision = 4,
    ): float {
        $userCurrency = User::getIdentity()->userSettings->currency;

        return $userCurrency === self::CURRENCY_RUB
            ? self::convertCNYtoRUB($amount, $orderId, $precision)
            : round($amount, $precision);
    }

    public static function putInUserCurrency(
        float $amount,
        int $orderId = 0,
        int $precision = 4,
    ): float {
        $userCurrency = User::getIdentity()->userSettings->currency;

        return $userCurrency === self::CURRENCY_RUB
            ? self::convertRUBtoCNY($amount, $orderId, $precision)
            : round($amount, $precision);
    }

    public static function convertToInitialCurrency(
        string $fromCurrency,
        float $amount,
        int $orderId = 0,
        int $precision = 4,
    ) {
        return self::convertSimpleRate(
            $fromCurrency,
            self::CURRENCY_CNY,
            $amount,
            $precision,
            $orderId,
        );
    }

    public static function convertCNYtoRUB(
        float $amount,
        int $orderId = 0,
        int $precision = 4,
    ): float {
        return self::convertSimpleRate(
            self::CURRENCY_CNY,
            self::CURRENCY_RUB,
            $amount,
            $precision,
            $orderId,
        );
    }

    public static function convertRUBtoCNY(
        float $amount,
        int $orderId = 0,
        int $precision = 4,
    ): float {
        return self::convertSimpleRate(
            self::CURRENCY_RUB,
            self::CURRENCY_CNY,
            $amount,
            $precision,
            $orderId,
        );
    }

    public static function convertUSDtoCNY(
        float $amount,
        int $orderId = 0,
        int $precision = 4,
    ): float {
        return self::convertSimpleRate(
            self::CURRENCY_USD,
            self::CURRENCY_CNY,
            $amount,
            $precision,
            $orderId,
        );
    }

    public static function convertCNYtoUSD(
        float $amount,
        int $orderId = 0,
        int $precision = 4,
    ): float {
        return self::convertSimpleRate(
            self::CURRENCY_CNY,
            self::CURRENCY_USD,
            $amount,
            $precision,
            $orderId,
        );
    }

    public static function convertUSDtoRUB(
        float $amount,
        int $orderId = 0,
        int $precision = 4,
    ): float {
        return self::convertCrossRate(
            self::CURRENCY_USD,
            self::CURRENCY_RUB,
            $amount,
            $precision,
            $orderId,
        );
    }

    public static function convertRUBtoUSD(
        float $amount,
        int $orderId = 0,
        int $precision = 4,
    ): float {
        return self::convertCrossRate(
            self::CURRENCY_RUB,
            self::CURRENCY_USD,
            $amount,
            $precision,
            $orderId,
        );
    }

    private static function convertSimpleRate(
        string $fromCurrency,
        string $toCurrency,
        float $amount,
        int $precision = 4,
        int $orderId = 0,
    ): float {
        try {
            $currentRate = self::getRate();

            if ($orderId) {
                $currentRate = self::getOrderRate($orderId);
            }

            return round(
                $amount *
                    ($currentRate[$toCurrency] / $currentRate[$fromCurrency]),
                $precision,
            );
        } catch (Throwable) {
            return 0;
        }
    }

    private static function convertCrossRate(
        string $fromCurrency,
        string $toCurrency,
        float $amount,
        int $precision = 4,
        int $orderId = 0,
    ): float {
        $amountInBaseCurrency = self::convertSimpleRate(
            $fromCurrency,
            self::CURRENCY_CNY,
            $amount,
            $precision,
            $orderId,
        );

        return self::convertSimpleRate(
            self::CURRENCY_CNY,
            $toCurrency,
            $amountInBaseCurrency,
            $precision,
            $orderId,
        );
    }
}
