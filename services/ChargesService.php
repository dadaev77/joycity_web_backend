<?php

namespace app\services;

use app\models\Charges;

class ChargesService
{
    protected $defaultCharges = [];


    public function __construct()
    {
        $this->defaultCharges = [
            'usd_charge' => $_ENV['USD_CHARGE'],
            'cny_charge' => $_ENV['CNY_CHARGE']
        ];
    }

    public static function updateCharges(int $usdCharge, int $cnyCharge): bool
    {
        $charges = Charges::find()->one() ?? new Charges();
        $charges->usd_charge = $usdCharge;
        $charges->cny_charge = $cnyCharge;
        return $charges->save();
    }

    public static function getCharges(): array
    {
        $charges = Charges::find()->one() ?? self::$defaultCharges;
        return $charges->toArray();
    }
}
