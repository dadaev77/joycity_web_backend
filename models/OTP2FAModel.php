<?php

namespace app\models;

use yii\db\Expression;

class OTP2FAModel extends \yii\db\ActiveRecord
{

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {

            if ($insert) {
                $this->created_at = new Expression('NOW()');
                $this->expires_at = new Expression('NOW() + INTERVAL 5 MINUTE');
            }
            return true;
        }
        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        /**
         * тут делаешь че то после сохранения
         */
    }

    public function rules()
    {
        /**
         * тут делаешь правила для валидации
         */
    }

    public function attributeLabels()
    {
        /**
         * тут делаешь метки для атрибутов
         */
    }
}
