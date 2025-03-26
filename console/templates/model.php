<?php

namespace $namespace;

use yii\db\ActiveQuery;
use Yii;
use Throwable;

class $className {

    /**
     * Это модель брат 
     * че тут и как делать, спросишь?
     * 
     * я без понятия, я только сделал шаблон
     */

    public function beforeSave($insert)
    {
        /**
         * тут делаешь че то перед сохранением
         */
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