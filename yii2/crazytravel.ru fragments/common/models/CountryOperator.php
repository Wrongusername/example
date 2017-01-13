<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ct.country_operator".
 *
 * @property integer $operator_id
 * @property integer $country_id
 * @property integer $operator_country_id
 */
class CountryOperator extends \yii\db\ActiveRecord {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'ct.country_operator';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['operator_id', 'country_id', 'operator_country_id'], 'required'],
            [['operator_id', 'country_id', 'operator_country_id'], 'integer'],
            [['operator_id', 'country_id'], 'unique', 'targetAttribute' => ['operator_id', 'country_id'], 'message' => 'The combination of Operator ID and Country ID has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'operator_id' => 'Operator ID',
            'country_id' => 'Country ID',
            'operator_country_id' => 'Operator Country ID',
        ];
    }
}
