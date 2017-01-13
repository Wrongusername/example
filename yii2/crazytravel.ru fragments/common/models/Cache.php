<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ct.cache".
 *
 * @property integer $operator_id
 * @property integer $company_id
 * @property integer $offer_id
 * @property integer $country_id
 * @property double $amount
 * @property integer $amount_currency_id
 * @property string $utime
 * @property double $price_per_day
 * @property integer $med_program_id
 */
class Cache extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ct.cache';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['operator_id', 'company_id', 'offer_id', 'country_id', 'amount', 'amount_currency_id', 'price_per_day', 'med_program_id'], 'required'],
            [['operator_id', 'company_id', 'offer_id', 'country_id', 'amount_currency_id', 'med_program_id'], 'integer'],
            [['amount', 'price_per_day'], 'number'],
            [['utime'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'operator_id' => 'Operator ID',
            'company_id' => 'Company ID',
            'offer_id' => 'Offer ID',
            'country_id' => 'Country ID',
            'amount' => 'Amount',
            'amount_currency_id' => 'Amount Currency ID',
            'utime' => 'Utime',
            'price_per_day' => 'Price Per Day',
            'med_program_id' => 'Med Program ID',
        ];
    }
}