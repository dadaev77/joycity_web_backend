<?php

namespace app\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class TestQueueJob extends BaseObject implements JobInterface
{
    public $data;
    
    public function execute($queue)
    {
        Yii::info('Test job executed with data: ' . $this->data, 'queue-test');
        return true;
    }
}