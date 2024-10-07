<?php

namespace app\services;


class ExchangeRateService
{
    // use open api to get exchange rates
    private const URL = "https://api.exchangerate-api.com/v4/latest/";
    private static $baseCurrency;
    private static $currencies;

    /**
     * @param array $currencies allow [currency codes] in low case
     * @param string $baseCurrency
     * @return array
     */
    public static function getRate(array $currencies, string $baseCurrency = 'USD')
    {
        try {

            self::$baseCurrency = $baseCurrency;
            $response = file_get_contents(self::URL . self::$baseCurrency);
            $data = json_decode($response, true);

            $outputPairs = [];
            foreach ($currencies as $currency) {
                $currency = strtoupper($currency);
                $outputPairs[self::$baseCurrency . '_' . $currency] = $data['rates'][$currency];
            }

            return array(
                'pairs' => $outputPairs,
                'date' => $data['date'],
            );
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
