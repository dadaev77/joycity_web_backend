<?php

namespace app\models;

use app\models\responseCodes\UserVerificationRequestCodes;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "user_verification_request".
 *
 * @property int $id
 * @property int $created_by_id
 * @property int $manager_id
 * @property int|null $approved_by_id
 * @property string $created_at
 * @property float $amount
 * @property int $status
 *
 * @property User $approvedBy
 * @property Chat $chat
 * @property User $createdBy
 * @property User $manager
 */
class UserVerificationRequest extends Base
{
    public const STATUS_WAITING = 0;
    public const STATUS_APPROVED = 1;

    public static function apiCodes(): UserVerificationRequestCodes
    {
        return UserVerificationRequestCodes::getStatic();
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_verification_request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_by_id', 'manager_id', 'created_at'], 'required'],
            [
                ['created_by_id', 'manager_id', 'approved_by_id', 'status'],
                'integer',
            ],
            [['created_at'], 'safe'],
            [['amount'], 'number'],
            [
                ['approved_by_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['approved_by_id' => 'id'],
            ],
            [
                ['created_by_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['created_by_id' => 'id'],
            ],
            [
                ['manager_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['manager_id' => 'id'],
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
            'created_by_id' => 'Created By ID',
            'manager_id' => 'Manager ID',
            'approved_by_id' => 'Approved By ID',
            'created_at' => 'Created At',
            'amount' => 'Amount',
            'status' => 'Status',
        ];
    }

    /**
     * Gets query for [[ApprovedBy]].
     *
     * @return ActiveQuery
     */
    public function getApprovedBy()
    {
        return $this->hasOne(User::class, ['id' => 'approved_by_id']);
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by_id']);
    }

    /**
     * Gets query for [[Manager]].
     *
     * @return ActiveQuery
     */
    public function getManager()
    {
        return $this->hasOne(User::class, ['id' => 'manager_id']);
    }
    /**
     * Gets query for [[Chat]].
     *
     * @return ActiveQuery
     */
    public function getChat()
    {
        return $this->hasOne(Chat::class, [])->where(['JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.verification_request_id"))' => $this->id]);
    }
}
