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

    public function execute($queue)
    {
        $entity = $this->type === 'product' ?
            \app\models\Product::findOne($this->id) :
            \app\models\Order::findOne($this->id);

        if (!$entity) {
            return 'Entity not found';
        }

        echo 'сущность: ' . json_encode($entity) . ' ' . $this->id . ' ' . $this->type;
        echo 'промт: ' . json_encode($this->data);
    }
}
