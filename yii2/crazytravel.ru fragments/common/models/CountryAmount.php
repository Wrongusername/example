<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ct.country_amount".
 */
class CountryAmount extends \yii\db\ActiveRecord {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'ct.country_amount';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['operator_id', 'country_id', 'company_id', 'currency_id', 'amount'], 'required'],
            [['operator_id', 'country_id', 'company_id', 'currency_id'], 'integer'],
            [['amount'], 'number'],
	    [['utime'], 'safe'],
            [['operator_id', 'country_id', 'company_id', 'amount', 'currency_id'], 'unique', 'targetAttribute' => ['operator_id', 'country_id', 'company_id', 'amount', 'currency_id'], 'message' => 'The combination of Operator ID, Country ID and Company ID, Amount, Currency ID has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'operator_id' => 'Operator ID',
            'country_id' => 'Country ID',
            'company_id' => 'Company ID',
            'amount' => 'Amount',
            'utime' => 'Last update time',
            'currency_id' => 'Currency ID',
        ];
    }
}
