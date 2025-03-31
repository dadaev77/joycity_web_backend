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
        try {
            echo "\n" . "\033[38;5;214m" . "************************************************" . "\033[0m";
            echo "\n" . "\033[38;5;214m" . "   [TS:NAME] " . $this->name . "\033[0m";
            echo "\n" . "\033[38;5;214m" . "   [TS:DESCRIPTION] " . $this->description . "\033[0m";
            echo "\n" . "\033[38;5;214m" . "   [TS:TYPE] " . $this->type . "\033[0m";
            echo "\n" . "\033[38;5;214m" . "   [TS:ID] " . $this->id . "\033[0m";
            echo "\n" . "\033[38;5;214m" . "************************************************" . "\033[0m";
            $translations = \app\services\TranslationService::translate($this->data);
            if (is_string($translations)) {
                $translations = json_decode($translations, true);
            }
            if ($this->type === 'product') {
                echo "\n" . "\033[38;5;214m" . "   [TS:PRODUCT_ID] " . $this->id . "\033[0m";
                $this->updateProduct($translations);
            } else {
                echo "\n" . "\033[38;5;214m" . "   [TS:ORDER_ID] " . $this->id . "\033[0m";
                $this->updateOrder($translations);
            }
            return true;
        } catch (Exception $e) {
            echo "\n" . "\033[38;5;214m" . "   [TS:ERROR] " . $e->getMessage() . "\033[0m";
        }
        echo "\n" . "\033[38;5;214m" . "************************************************" . "\033[0m";
    }
    private function updateProduct(
        $translations
    ) {
        $product = \app\models\Product::findOne($this->id);
        echo "\n" . "\033[38;5;214m" . "   [TS:PRODUCT_ID] " . $product->id . "\033[0m";
        if (!$product) {
            echo "\n" . "\033[38;5;214m" . "   [TS:PRODUCT_NOT_FOUND] " . $this->id . "\033[0m";
            return;
        }
        foreach ($translations as $lang => $value) {
            $product->{'name_' . $lang} = $value['name'];
            $product->{'description_' . $lang} = $value['description'];
            echo "\n" . "\033[38;5;214m" . "   [TS:PRODUCT_TRANSLATION_SAVED] " . strtoupper($lang) . "\033[0m";
        }
        $product->save();
        echo "\n" . "\033[38;5;214m" . "   [TS:PRODUCT_SAVED] " . $this->id . "\033[0m";
    }
    private function updateOrder(
        $translations
    ) {
        $order = \app\models\Order::findOne($this->id);
        echo "\n" . "\033[38;5;214m" . "   [TS:ORDER_ID] " . $order->id . "\033[0m";
        if (!$order) {
            echo "\n" . "\033[38;5;214m" . "   [TS:ORDER_NOT_FOUND] " . $this->id . "\033[0m";
            return;
        }

        foreach ($translations as $lang => $values) {
            $order->{'product_name_' . $lang} = $values['name'];
            $order->{'product_description_' . $lang} = $values['description'];
            echo "\n" . "\033[38;5;214m" . "   [TS:ORDER_TRANSLATION_SAVED] " . strtoupper($lang) . "\033[0m";
        }

        $order->save();
        echo "\n" . "\033[38;5;214m" . "   [TS:ORDER_SAVED] " . $this->id . "\033[0m";
    }
}
