<?php

namespace app\jobs;

use yii\base\BaseObject;
use yii\queue\JobInterface;

class TestJob extends BaseObject implements JobInterface
{
    public function execute($queue)
    {
        echo "asdasdasdasd";
    }
}
