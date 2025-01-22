<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Class Heartbeat
 * @package app\models
 *
 * @property int $id
 * @property string $service_name
 * @property string $service_url
 * @property string $last_run_at
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class Heartbeat extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'heartbeat';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['service_name', 'last_run_at', 'status'], 'required'],
            [['last_run_at'], 'safe'],
            [['service_name', 'status'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'service_name' => 'Service Name',
            'last_run_at' => 'Last Run At',
            'status' => 'Status',
        ];
    }
}
