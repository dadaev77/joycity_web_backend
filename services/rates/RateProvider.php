<?php

namespace app\services\rates;

use app\models\OrderRate;
use app\models\Rate;

class RateProvider
{
    private array $currentRate = [];
    private array $orderRates = [];
    private ChargesProvider $chargesProvider;

    public function __construct(ChargesProvider $chargesProvider)
    {
        $this->chargesProvider = $chargesProvider;
    }

    /**
     * Возвращает текущий курс валют.
     * @return array Курсы валют
     */
    public function getCurrentRate(): array
    {
        if (empty($this->currentRate)) {
            $rate = Rate::find()
                ->orderBy(['id' => SORT_DESC])
                ->asArray()
                ->one() ?? ['USD' => 1.0, 'CNY' => 1.0];

            if (!isset($rate['USD'], $rate['CNY'])) {
                throw new \RuntimeException('Invalid rate data: USD or CNY missing.');
            }

            $this->currentRate = $this->applyCharges($rate);
        }
        return $this->currentRate;
    }

    /**
     * Возвращает курс для конкретного заказа или текущий курс.
     * @param int $orderId ID заказа.
     * @return array Курсы валют.
     */
    public function getOrderRate(int $orderId): array
    {
        if (!isset($this->orderRates[$orderId])) {
            $orderRate = OrderRate::find()
                ->asArray()
                ->where(['order_id' => $orderId])
                ->one();
            $rate = $orderRate ?: $this->getCurrentRate();
            if (!isset($rate['USD'], $rate['CNY'])) {
                throw new \RuntimeException('Invalid order rate data: USD or CNY missing.');
            }
            $this->orderRates[$orderId] = $rate;
        }
        return $this->orderRates[$orderId];
    }

    /**
     * Применяет наценки к курсам валют.
     * @param array $rate Курсы валют.
     * @return array Курсы с наценками.
     */
    private function applyCharges(array $rate): array
    {
        $rate['USD'] *= $this->chargesProvider->chargeUsd;
        $rate['CNY'] *= $this->chargesProvider->chargeCny;
        return $rate;
    }
}
