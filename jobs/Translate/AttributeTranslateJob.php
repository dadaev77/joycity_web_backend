<?php

namespace app\jobs\Translate;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use Exception;
use Yii;

class AttributeTranslateJob extends BaseObject implements JobInterface
{
    public $name;
    public $description;
    public $type;
    public $id;
    public $data;
    public function execute($queue)
    {
        $result = \app\services\TranslationService::translate($this->data);
        var_dump($result);
    }
}
