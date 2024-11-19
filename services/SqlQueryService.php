<?php

namespace app\services;

class SqlQueryService
{
    public static function getBuyerSelect()
    {
        return [
            'id',
            'email',
            'name',
            'surname',
            'organization_name',
            'phone_number',
            'nickname',
            'country',
            'city',
            'address',
            'role',
            'rating',
            'feedback_count',
            'is_deleted',
            'is_email_confirmed',
            'is_verified',
            'avatar_id',
            'description',
            'personal_id',
        ];
    }

    public static function getUserSelect()
    {
        return [
            'id',
            'email',
            'name',
            'surname',
            'organization_name',
            'phone_number',
            'country',
            'city',
            'address',
            'role',
            'is_deleted',
            'is_email_confirmed',
            'is_verified',
            'avatar_id',
            'description',
            'phone_country_code',
            'personal_id',
        ];
    }
}
