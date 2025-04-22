<?php

namespace app\models;

use app\services\RateService;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "user_settings".
 *
 * @property int $id
 * @property int $enable_notifications
 * @property string $currency
 * @property string $application_language
 * @property string $chat_language
 * @property int $user_id
 * @property int $use_only_selected_categories
 * @property int|null $high_workload
 *
 * @property User $user
 */
class UserSettings extends Base
{
    public const NOTIFICATION_ON = 1;
    public const NOTIFICATION_OFF = 0;

    public const CURRENCY_RUB = RateService::CURRENCY_RUB;
    public const CURRENCY_CNY = RateService::CURRENCY_CNY;
    public const CURRENCY_USD = RateService::CURRENCY_USD;

    public const APPLICATION_LANGUAGE_RU = 'ru';
    public const APPLICATION_LANGUAGE_EN = 'en';
    public const APPLICATION_LANGUAGE_ZH = 'zh';

    public const CHAT_LANGUAGE_RU = 'ru';
    public const CHAT_LANGUAGE_EN = 'en';
    public const CHAT_LANGUAGE_ZH = 'zh';

    public const OFF_SELECTED_CATEGORIES = 0;
    public const ON_SELECTED_CATEGORIES = 1;

    public const ON_HIGH_WORKLOAD = 1;
    public const OFF_HIGH_WORKLOAD = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
                [
                    'enable_notifications',
                    'user_id',
                    'use_only_selected_categories',
                    'high_workload',
                ],
                'integer',
            ],
            [
                [
                    'currency',
                    'application_language',
                    'chat_language',
                    'user_id',
                ],
                'required',
            ],
            [
                ['currency', 'application_language', 'chat_language'],
                'string',
                'max' => 10,
            ],
            [['user_id'], 'unique'],
            [
                ['user_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['user_id' => 'id'],
            ],

            // custom
            [
                ['enable_notifications'],
                'in',
                'range' => [self::NOTIFICATION_ON, self::NOTIFICATION_OFF],
            ],
            [
                ['use_only_selected_categories'],
                'in',
                'range' => [
                    self::OFF_SELECTED_CATEGORIES,
                    self::ON_SELECTED_CATEGORIES,
                ],
            ],
            [
                ['currency'],
                'in',
                'range' => [self::CURRENCY_RUB, self::CURRENCY_CNY, self::CURRENCY_USD],
            ],
            [
                ['application_language'],
                'in',
                'range' => [
                    self::APPLICATION_LANGUAGE_RU,
                    self::APPLICATION_LANGUAGE_EN,
                    self::APPLICATION_LANGUAGE_ZH,
                ],
            ],
            [
                ['chat_language'],
                'in',
                'range' => [
                    self::CHAT_LANGUAGE_RU,
                    self::CHAT_LANGUAGE_EN,
                    self::CHAT_LANGUAGE_ZH,
                ],
            ],
            [
                ['high_workload'],
                'in',
                'range' => [self::ON_HIGH_WORKLOAD, self::OFF_HIGH_WORKLOAD],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'enable_notifications' => 'Enable Notifications',
            'currency' => 'Currency',
            'application_language' => 'Application Language',
            'chat_language' => 'Chat Language',
            'user_id' => 'User ID',
            'use_only_selected_categories' => 'Use Only Selected Categories',
            'high_workload' => 'High Workload',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
