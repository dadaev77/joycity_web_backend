<?php

namespace $namespace;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use Exception;
use Yii;

class $className extends BaseObject implements JobInterface
{
    /**
     * Братан, это джобка
     * а как и че тут дальше делать, спросишь?
     * 
     * я без понятия, я только сделал шаблон
     */

    public function execute($queue)
    {
        /**
         *  а тут короче что-то делаем
         */
    }
}