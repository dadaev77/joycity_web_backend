<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\FeedbackUser;

class FeedbackUserOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = FeedbackUser::find()
            ->with(['attachments'])
            ->where(['id' => $ids]);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);

            unset($info['feedbackUserLinkAttachments']);

            return $info;
        }, $query->all());
    }
}
