<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ct.cover_type_operator".
 *
 * @property integer $operator_id
 * @property integer $cover_id
 * @property integer $operator_cover_id
 *
 * @property CtCoverType $cover
 * @property CtOperator $operator
 */
class CoverTypeOperator extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ct.cover_type_operator';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['operator_id', 'cover_id', 'operator_cover_id'], 'required'],
            [['operator_id', 'cover_id', 'operator_cover_id'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'operator_id' => 'Operator ID',
            'cover_id' => 'Cover ID',
            'operator_cover_id' => 'Operator Cover ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCover()
    {
        return $this->hasOne(CoverType::className(), ['id' => 'cover_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperator()
    {
        return $this->hasOne(Operator::className(), ['id' => 'operator_id']);
    }
}