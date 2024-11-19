<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\Chat;
use app\models\Order;
use app\services\SqlQueryService;

class ChatOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = Chat::find()
            ->with([
                'order',
                'userVerificationRequest',
                'chatUsers' => fn($q) => $q->with([
                    'user' => fn($q) => $q
                        ->select(SqlQueryService::getUserSelect())
                        ->with(['avatar']),
                ]),
            ])
            ->orderBy(self::getOrderByIdExpression($ids))
            ->where(['id' => $ids]);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);
            $info['chatUsers'] = array_map(
                static fn($item) => $item['user'],
                $info['chatUsers'],
            );

            if ($info['order']) {
                $info['order']['type'] = in_array(
                    $info['order']['status'],
                    Order::STATUS_GROUP_ORDER,
                    true,
                )
                    ? 'order'
                    : 'request';
            }

            unset(
                $info['order_id'],
                $info['created_by_id'],
                $info['user_verification_request_id'],
            );

            return $info;
        }, $query->all());
    }
}
