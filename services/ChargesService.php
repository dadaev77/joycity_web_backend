<?php

namespace app\services;

use app\models\Charges;
use Yii;

class ChargesService
{
    /**
     * Получает текущие значения наценок
     * @return array
     */
    public static function getCurrentCharges(): array
    {
        $charges = Charges::getCurrentCharges();
        if (!$charges) {
            // Возвращаем значения по умолчанию
            return [
                'usd_charge' => 2,
                'cny_charge' => 5
            ];
        }
        return $charges;
    }

    /**
     * Применяет наценку к курсу USD
     * @param float $rate Исходный курс
     * @return float Курс с наценкой
     */
    public static function applyUsdCharge(float $rate): float
    {
        $charges = self::getCurrentCharges();
        return $rate * (1 + $charges['usd_charge'] / 100);
    }

    /**
     * Применяет наценку к курсу CNY
     * @param float $rate Исходный курс
     * @return float Курс с наценкой
     */
    public static function applyCnyCharge(float $rate): float
    {
        $charges = self::getCurrentCharges();
        return $rate * (1 + $charges['cny_charge'] / 100);
    }

    /**
     * Обновляет значения наценок
     * @param int $usdCharge Наценка на USD
     * @param int $cnyCharge Наценка на CNY
     * @return bool
     */
    public static function updateCharges(int $usdCharge, int $cnyCharge): bool
    {
        $charges = Charges::find()->one() ?? new Charges();
        $charges->usd_charge = $usdCharge;
        $charges->cny_charge = $cnyCharge;
        return $charges->save();
    }
} 