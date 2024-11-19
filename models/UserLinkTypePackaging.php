<?php

namespace app\models;

use yii\db\ActiveQuery;

/**
 * This is the model class for table "type_packaging_has_user".
 *
 * @property int $id
 * @property int $type_packaging_id
 * @property int $user_id
 *
 * @property TypePackaging $typePackaging
 * @property User $user
 */
class UserLinkTypePackaging extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_link_type_packaging';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type_packaging_id', 'user_id'], 'required'],
            [['type_packaging_id', 'user_id'], 'integer'],
            [
                ['type_packaging_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => TypePackaging::class,
                'targetAttribute' => ['type_packaging_id' => 'id'],
            ],
            [
                ['user_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['user_id' => 'id'],
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
            'type_packaging_id' => 'Type Packaging ID',
            'user_id' => 'User ID',
        ];
    }

    /**
     * Gets query for [[TypePackaging]].
     *
     * @return ActiveQuery
     */
    public function getTypePackaging()
    {
        return $this->hasOne(TypePackaging::class, [
            'id' => 'type_packaging_id',
        ]);
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
