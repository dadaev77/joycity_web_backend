<?php

namespace app\services\rates;

class CurrencyConverter
{
    private RateProvider $rateProvider;

    private int $precision = 4;

    public function __construct(RateProvider $rateProvider)
    {
        $this->rateProvider = $rateProvider;
    }

    /**
     * Устанавливает точность округления.
     * @param int $precision Количество знаков после запятой.
     */
    public function setPrecision(int $precision): void
    {
        $this->precision = $precision;
    }

    /**
     * Конвертирует значение из одной валюты в другую.
     * @param float $value Значение для конвертации.
     * @param string $fromCurrency Исходная валюта.
     * @param string $toCurrency Целевая валюта.
     * @param int|null $orderId ID заказа (опционально).
     * @return float Конвертированное значение.
     * @throws \InvalidArgumentException Если курс отсутствует.
     */
    public function convert(float $value, string $fromCurrency, string $toCurrency, ?int $orderId = null): float
    {
        if ($value == 0 || $fromCurrency === $toCurrency) {
            return $value;
        }

        $rate = $orderId ? $this->rateProvider->getOrderRate($orderId) : $this->rateProvider->getCurrentRate();

        if (!isset($rate[$fromCurrency], $rate[$toCurrency])) {
            throw new \InvalidArgumentException("Missing rate for $fromCurrency or $toCurrency");
        }

        $result = $value * ($rate[$fromCurrency] / $rate[$toCurrency]);
        return round($result, $this->precision);
    }

    /**
     * Конвертирует массив цен.
     * @param array $prices Массив цен.
     * @param string $fromCurrency Исходная валюта.
     * @param string $toCurrency Целевая валюта.
     * @param int|null $orderId ID заказа (опционально).
     * @return array Конвертированный массив.
     */
    public function convertArray(array $prices, string $fromCurrency, string $toCurrency, ?int $orderId = null): array
    {
        return array_map(function ($price) use ($fromCurrency, $toCurrency, $orderId) {
            return is_numeric($price) ? $this->convert($price, $fromCurrency, $toCurrency, $orderId) : $price;
        }, $prices);
    }

    /**
     * Конвертирует цены в массиве данных по указанным ключам.
     * @param array $data Массив данных.
     * @param array $priceKeys Ключи, содержащие цены.
     * @param string $fromCurrency Исходная валюта.
     * @param string $toCurrency Целевая валюта.
     * @param int|null $orderId ID заказа (опционально).
     * @return array Обработанный массив данных.
     */
    public function convertData(array $data, array $priceKeys, string $fromCurrency, string $toCurrency, ?int $orderId = null): array
    {
        foreach ($priceKeys as $key) {
            if (isset($data[$key])) {
                if (is_array($data[$key])) {
                    $data[$key] = $this->convertArray($data[$key], $fromCurrency, $toCurrency, $orderId);
                } elseif (is_numeric($data[$key])) {
                    $data[$key] = $this->convert($data[$key], $fromCurrency, $toCurrency, $orderId);
                }
            }
        }
        return $data;
    }
}
