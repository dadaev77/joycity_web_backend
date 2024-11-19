<?php

namespace app\services;

use app\components\responseFunction\Result;
use app\models\AppOption;

class OptionsService
{
    public const MPSTATS_TOKEN = 'mpstats_token';

    public const ALL_KEYS = [self::MPSTATS_TOKEN];

    public static function updateByKey(string $key, string $value)
    {
        if (!in_array($key, self::ALL_KEYS)) {
            return Result::error([
                'errors' => [
                    'key' => 'Ошибка: Не разрешенный ключ.',
                ],
            ]);
        }

        $optionModel = AppOption::findOne(['key' => $key]);

        if ($optionModel) {
            $optionModel->value = $value;
            $optionModel->updated_at = date('Y-m-d H:i:s');

            if ($optionModel->save()) {
                return Result::success($optionModel);
            }

            return Result::notValid($optionModel->getFirstErrors());
        }

        return Result::notFound();
    }

    public static function updateById(int $id, string $value)
    {
        $optionModel = AppOption::findOne($id);

        if (!$optionModel) {
            return Result::notFound();
        }

        $optionModel->value = $value;
        $optionModel->updated_at = date('Y-m-d H:i:s');

        if ($optionModel->save()) {
            return Result::success($optionModel);
        }

        return Result::notValid($optionModel->getFirstErrors());
    }

    public static function getValue(string $key)
    {
        if (!in_array($key, self::ALL_KEYS)) {
            return '';
        }

        $optionModel = AppOption::findOne(['key' => $key]);

        if ($optionModel === null) {
            return '';
        }

        return $optionModel->value;
    }

    public static function deployFields()
    {
        $existingKeys = AppOption::find()
            ->select(['key'])
            ->where(['key' => self::ALL_KEYS])
            ->column();

        $newKeys = array_diff(self::ALL_KEYS, $existingKeys);
        $insertedIds = [];

        foreach ($newKeys as $key) {
            $appOption = new AppOption([
                'key' => $key,
            ]);

            if (!$appOption->save()) {
                return Result::errors($appOption->getFirstErrors());
            }

            $insertedIds[] = $appOption->key;
        }

        return Result::success($insertedIds);
    }

    public static function getEntity(int $id)
    {
        $model = AppOption::findOne($id);

        if (!$model) {
            return Result::notFound();
        }

        return Result::success($model);
    }
}
