<?php

namespace app\jobs;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use Yii;

class TestJob extends BaseObject implements JobInterface
{
    public function execute($queue)
    {
        try {
            Yii::$app->telegramLog->send('info', 'test job');
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        echo "\nmessage sent";
        return true;
    }
}
