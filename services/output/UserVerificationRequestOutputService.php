<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\UserVerificationRequest;
use app\services\RateService;
use app\services\SqlQueryService;

class UserVerificationRequestOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = UserVerificationRequest::find()
            ->with([
                'createdBy' => fn($q) => $q->select(
                    SqlQueryService::getUserSelect(),
                ),
                'manager' => fn($q) => $q->select(
                    SqlQueryService::getUserSelect(),
                ),
                'approvedBy' => fn($q) => $q->select(
                    SqlQueryService::getUserSelect(),
                ),
                'chat',
            ])
            ->where(['id' => $ids])
            ->orderBy(['id' => SORT_DESC]);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);
            // $info['amount'] = RateService::outputInUserCurrency(
            //     $info['amount'],

            // );

            unset(
                $info['created_by_id'],
                $info['manager_id'],
                $info['approved_by_id'],
                $info['chat']['order_id'],
                $info['chat']['user_verification_request_id'],
            );

            return $info;
        }, $query->all());
    }
}
