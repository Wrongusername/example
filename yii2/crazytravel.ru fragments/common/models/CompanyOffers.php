<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ct.company_offers".
 *
 * @property integer $operator_id
 * @property integer $offer_id
 * @property integer $company_id
 * @property string $name
 * @property string $description
 *
 * @property CompanyOperator $companyOperator
 * @property Operator $operator
 */
class CompanyOffers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ct.company_offers';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['operator_id', 'offer_id', 'name', 'description'], 'required'],
            [['operator_id', 'offer_id', 'company_id'], 'integer'],
            [['description'], 'string'],
            [['name'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'operator_id' => 'Operator ID',
            'offer_id' => 'Offer ID',
            'company_id' => 'Company ID',
            'name' => 'Name',
            'description' => 'Description',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyOperator()
    {
        return $this->hasOne(CompanyOperator::className(), ['operator_id' => 'operator_id', 'operator_company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperator()
    {
        return $this->hasOne(Operator::className(), ['id' => 'operator_id']);
    }
}