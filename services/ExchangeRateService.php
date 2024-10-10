<?php

namespace app\services;


class ExchangeRateService
{
    // use open api to get exchange rates
    private const URL = "https://www.cbr-xml-daily.ru/daily_json.js";
    private static $currencies;

    /**
     * @param array $currencies allow [currency codes] in low case
     * @param string $baseCurrency
     * @return array
     */
    public static function getRate(array $currencies)
    {
        try {

            $response = json_decode(file_get_contents(self::URL), true);
            $data = $response['Valute'];

            $outputPairs = [];
            foreach ($currencies as $currency) {
                $currency = strtoupper($currency);
                $outputPairs[$currency] = $data[$currency]['Value'];
            }

            return array(
                'data' => $outputPairs
            );
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
