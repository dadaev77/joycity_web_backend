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
        $entity = $this->type === 'product' ?
            \app\models\Product::find()->where(['id' => $this->id])->one() :
            \app\models\Order::find()->where(['id' => $this->id])->one();

        if (!$entity) {
            return 'Entity not found';
        }
        $translation = \app\services\TranslationService::translate($this->data);
        echo 'перевод: ' . json_encode($translation);
    }
}
