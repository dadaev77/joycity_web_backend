<?php

namespace app\services;

class ExchangeRateService
{
    private const BASE_URL = "http://api.currencylayer.com/live";
    private static $currencies;

    /**
     * @param array $currencies allow [currency codes] in low case
     * @return array
     */
    public static function getRate(array $currencies)
    {
        try {
            $apiKey = $_ENV['CURRENCY_LAYER_API_KEY'];
            $currenciesList = strtoupper(implode(',', $currencies));

            $url = self::BASE_URL . "?access_key=" . $apiKey . "&currencies=RUB," . $currenciesList . "&source=USD&format=1";
            $response = json_decode(file_get_contents($url), true);

            if (!$response['success']) {
                throw new \Exception('Currency Layer API error: ' . ($response['error']['info'] ?? 'Unknown error'));
            }

            $rates = $response['quotes'];
            $usdToRub = $rates['USDRUB'];

            $outputPairs = [];
            foreach ($currencies as $currency) {
                $currency = strtoupper($currency);
                // Convert through USD rate since Currency Layer uses USD as base
                if ($currency === 'USD') {
                    $outputPairs[$currency] = $usdToRub;
                } else {
                    $usdToCurrency = $rates['USD' . $currency];
                    $outputPairs[$currency] = ($usdToRub / $usdToCurrency);
                }
            }

            return [
                'data' => $outputPairs
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
