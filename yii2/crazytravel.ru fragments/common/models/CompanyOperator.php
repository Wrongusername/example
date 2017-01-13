<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ct.company_operator".
 *
 * @property integer $company_id
 * @property integer $operator_id
 * @property integer $operator_company_id
 *
 * @property CtCompany $company
 * @property CtOperator $operator0
 */
class CompanyOperator extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ct.company_operator';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_id', 'operator_id', 'operator_company_id'], 'required'],
            [['company_id', 'operator_id', 'operator_company_id'], 'integer'],
	    [['enabled'], 'boolean']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'company_id' => 'Company ID',
            'operator' => 'Operator',
            'operator_id' => 'Operator ID',
	    'enabled' => 'Enabled'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperator0()
    {
        return $this->hasOne(Operator::className(), ['id' => 'operator_id']);
    }
}