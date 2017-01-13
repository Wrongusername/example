<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ct.risk_type".
 *
 * @property integer $id
 * @property string $name
 *
 * @property CtRiskTypeOperator[] $ctRiskTypeOperators
 * @property CtOperator[] $operators
 */
class RiskType extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ct.risk_type';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 60]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRiskTypeOperators()
    {
        return $this->hasMany(RiskTypeOperator::className(), ['risk_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperators()
    {
        return $this->hasMany(Operator::className(), ['id' => 'operator_id'])->viaTable('risk_type_operator', ['risk_id' => 'id']);
    }
}