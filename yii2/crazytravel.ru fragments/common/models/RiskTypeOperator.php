<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ct.risk_type_operator".
 *
 * @property integer $operator_id
 * @property integer $risk_id
 * @property integer $operator_risk_id
 *
 * @property CtOperator $operator
 * @property CtRiskType $risk
 */
class RiskTypeOperator extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ct.risk_type_operator';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['operator_id', 'risk_id', 'operator_risk_id'], 'required'],
            [['operator_id', 'risk_id', 'operator_risk_id'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'operator_id' => 'Operator ID',
            'risk_id' => 'Risk ID',
            'operator_risk_id' => 'Operator Risk ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperator()
    {
        return $this->hasOne(Operator::className(), ['id' => 'operator_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRisk()
    {
        return $this->hasOne(RiskType::className(), ['id' => 'risk_id']);
    }
}