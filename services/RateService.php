<?php

namespace app\services;

use app\services\rates\CurrencyConverter;
use app\services\rates\RateProvider;
use app\services\rates\ChargesProvider;

class RateService
{
    public const CURRENCY_RUB = 'RUB';
    public const CURRENCY_CNY = 'CNY';
    public const CURRENCY_USD = 'USD';

    private CurrencyConverter $converter;
    public static int $SYMBOLS_AFTER_DECIMAL_POINT = 4;

    /**
     * Инициализирует сервис с новым конвертером.
     */
    public function __construct()
    {
        $this->converter = new CurrencyConverter(new RateProvider(new ChargesProvider()));
        $this->converter->setPrecision(self::$SYMBOLS_AFTER_DECIMAL_POINT);
    }

    /**
     * Устанавливает количество знаков после запятой для округления.
     * @param int $symbolsAfterDecimalPoint Количество знаков.
     */
    public static function setSADP(int $symbolsAfterDecimalPoint): void
    {
        self::$SYMBOLS_AFTER_DECIMAL_POINT = $symbolsAfterDecimalPoint;
    }

    /**
     * Возвращает текущий курс валют.
     * @return array
     */
    public static function getRate(): array
    {
        return (new RateProvider(new ChargesProvider()))->getCurrentRate();
    }

    /**
     * Конвертирует одно значение из одной валюты в другую.
     * @param float $value Значение для конвертации.
     * @param string $fromCurrency Исходная валюта.
     * @param string $toCurrency Целевая валюта.
     * @return float Конвертированное значение.
     * @param int|null $orderId Идентификатор заказа.
     */
    public static function convertValue(float $value, string $fromCurrency, string $toCurrency, int $orderId): float
    {
        return (new self())->converter->convert($value, $fromCurrency, $toCurrency, $orderId);
    }

    /**
     * Конвертирует массив цен из одной валюты в другую.
     * @param array $prices Массив цен.
     * @param string $fromCurrency Исходная валюта.
     * @param string $toCurrency Целевая валюта.
     * @return array Конвертированный массив цен.
     */
    public static function convertPrices(array $prices, string $fromCurrency, string $toCurrency): array
    {
        return (new self())->converter->convertArray($prices, $fromCurrency, $toCurrency);
    }

    /**
     * Конвертирует цены в массиве данных по указанным ключам.
     * @param array $data Массив данных.
     * @param array $priceKeys Ключи, содержащие цены.
     * @param string $fromCurrency Исходная валюта.
     * @param string $toCurrency Целевая валюта.
     * @return array Обработанный массив данных.
     */
    public static function convertDataPrices(array $data, array $priceKeys, string $fromCurrency, string $toCurrency): array
    {
        return (new self())->converter->convertData($data, $priceKeys, $fromCurrency, $toCurrency);
    }
}
