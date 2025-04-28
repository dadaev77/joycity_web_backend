<?php

namespace app\services\rates;

class ChargesProvider
{
    public $chargeUsd = 1.07;
    public $chargeCny = 1.05;

    public function __construct()
    {
        $charges = \app\models\Charges::find()->orderBy(['id' => SORT_DESC])->one();
        if ($charges) {
            $this->chargeUsd = $charges->usd_charge;
            $this->chargeCny = $charges->cny_charge;
        }
    }
}
