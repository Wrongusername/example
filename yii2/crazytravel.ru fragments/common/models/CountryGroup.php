<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ct.country_group".
 */
class CountryGroup extends \yii\db\ActiveRecord {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'ct.country_group';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id'], 'required'],
            [['id'], 'integer'],
            [['date_create'], 'safe'],
            [['active'], 'boolean'],
            [['name'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'date_create' => 'Date Create',
            'active' => 'Active',
        ];
    }
}
