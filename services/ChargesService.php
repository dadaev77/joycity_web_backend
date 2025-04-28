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
        $charges = new Charges();

        $charges->usd_charge = $usdCharge;
        $charges->cny_charge = $cnyCharge;

        return $charges->save();
    }

    public static function getCharges(): array
    {
        $service = new self();

        $charges = Charges::find()
            ->orderBy(['id' => SORT_DESC])
            ->one();

        if (!$charges) {
            return $service->defaultCharges;
        }
        return $charges->toArray();
    }
}
