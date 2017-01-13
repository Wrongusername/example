<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ct.operator".
 */
class Operator extends \yii\db\ActiveRecord {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'ct.operator';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code', 'name'], 'required'],
            [['date_create'], 'safe'],
            [['active'], 'boolean'],
            [['code'], 'string', 'max' => 16],
            [['name'], 'string', 'max' => 50],
            [['code'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'name' => 'Name',
            'date_create' => 'Date Create',
            'active' => 'Active',
        ];
    }
}
