<?php

namespace app\models;

use yii\db\ActiveQuery;

/**
 * This is the model class for table "chat".
 *
 * @property int $id
 * @property string $created_at
 * @property string $twilio_id
 * @property string $name
 * @property string $group
 * @property string $type
 * @property int|null $order_id
 * @property int|null $user_verification_request_id
 * @property int $is_archive
 *
 * @property ChatUser[] $chatUsers
 * @property Order $order
 * @property UserVerificationRequest $userVerificationRequest
 */
class Chat extends Base
{
    public const GROUP_CLIENT_MANAGER = 'client_manager';
    public const GROUP_CLIENT_BUYER = 'client_buyer';
    public const GROUP_MANAGER_BUYER = 'manager_buyer';
    public const GROUP_CLIENT_FULFILMENT = 'client_fulfilment';
    public const GROUP_MANAGER_FULFILMENT = 'manager_fulfilment';
    public const GROUP_MANAGER_BUYER_CLIENT = 'manager_buyer_client';

    public const GROUPS_ALL = [
        self::GROUP_CLIENT_MANAGER,
        self::GROUP_CLIENT_BUYER,
        self::GROUP_CLIENT_FULFILMENT,
        self::GROUP_MANAGER_BUYER,
        self::GROUP_MANAGER_FULFILMENT,
    ];

    public const TYPE_ORDER = 'order';
    public const TYPE_VERIFICATION = 'verification';

    public const TYPES_ALL = [self::TYPE_ORDER, self::TYPE_VERIFICATION];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'twilio_id', 'group', 'type'], 'required'],
            [['created_at'], 'safe'],
            [
                ['order_id', 'user_verification_request_id', 'is_archive'],
                'integer',
            ],
            [['twilio_id', 'name', 'group', 'type'], 'string', 'max' => 255],
            [['user_verification_request_id'], 'unique'],
            [
                ['order_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Order::class,
                'targetAttribute' => ['order_id' => 'id'],
            ],
            [
                ['user_verification_request_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => UserVerificationRequest::class,
                'targetAttribute' => ['user_verification_request_id' => 'id'],
            ],

            // custom validations
            [['group'], 'in', 'range' => self::GROUPS_ALL],
            [['type'], 'in', 'range' => self::TYPES_ALL],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_at' => 'Created At',
            'twilio_id' => 'Twilio ID',
            'name' => 'Name',
            'group' => 'Group',
            'type' => 'Type',
            'order_id' => 'Order ID',
            'user_verification_request_id' => 'User Verification Request ID',
            'is_archive' => 'Is Archive',
        ];
    }

    /**
     * Gets query for [[ChatUsers]].
     *
     * @return ActiveQuery
     */
    public function getChatUsers()
    {
        return $this->hasMany(ChatUser::class, ['chat_id' => 'id']);
    }

    /**
     * Gets query for [[Order]].
     *
     * @return ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    /**
     * Gets query for [[UserVerificationRequest]].
     *
     * @return ActiveQuery
     */
    public function getUserVerificationRequest()
    {
        return $this->hasOne(UserVerificationRequest::class, [
            'id' => 'user_verification_request_id',
        ]);
    }

    public static function getGroupsMap()
    {
        return array_map(static function ($key) {
            return ['key' => $key, 'translate' => $key];
        }, self::GROUPS_ALL);
    }

    public static function getTypesMap()
    {
        return array_map(static function ($key) {
            return ['key' => $key, 'translate' => $key];
        }, self::TYPES_ALL);
    }
}
