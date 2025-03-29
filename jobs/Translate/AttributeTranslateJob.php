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
        var_dump([
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'id' => $this->id,
            'data' => $this->data
        ]);
    }
}
