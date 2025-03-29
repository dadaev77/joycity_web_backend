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
            echo "\n\033[32mНачало выполнения работы: " . $this->name . "\033[0m";
            $translations = \app\services\TranslationService::translate($this->data);
            if (is_string($translations)) {
                $translations = json_decode($translations, true);
            }
            if ($this->type === 'product') {
                echo "\n\033[32mОбновление продукта с ID: " . $this->id . "\033[0m";
                $this->updateProduct($translations);
            } else {
                echo "\n\033[32mОбновление заказа с ID: " . $this->id . "\033[0m";
                $this->updateOrder($translations);
            }
            echo "\n\033[32mРабота выполнена успешно: " . $this->name . "\033[0m";
            return true;
        } catch (Exception $e) {
            echo "\n\033[31mОшибка перевода: " . $e->getMessage() . "\033[0m";
        }
    }
    private function updateProduct(
        $translations
    ) {
        $product = \app\models\Product::findOne($this->id);
        if (!$product) {
            echo "\n\033[31mПродукт не найден с ID: " . $this->id . "\033[0m";
            return;
        }
        foreach ($translations as $lang => $value) {
            $product->{'name_' . $lang} = $value['name'];
            $product->{'description_' . $lang} = $value['description'];
            echo "\n\033[32mПеревод для языка " . $lang . " обновлен\033[0m";
        }

        $product->save();
        echo "\n\033[32mПродукт с ID: " . $this->id . " успешно обновлен\033[0m";
    }
    private function updateOrder(
        $translations
    ) {
        $order = \app\models\Order::findOne($this->id);

        if (!$order) {
            echo "\n\033[31mЗаказ не найден с ID: " . $this->id . "\033[0m";
            return;
        }

        foreach ($translations as $lang => $values) {
            $order->{'product_name_' . $lang} = $values['name'];
            $order->{'product_description_' . $lang} = $values['description'];
        }

        $order->save();
    }
}
