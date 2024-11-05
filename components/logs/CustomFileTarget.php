<?php

namespace app\components\logs;

use yii\log\FileTarget;

class CustomFileTarget extends FileTarget
{
    public function export()
    {
        unset($this->messages[1]);
        parent::export();
    }
}
