<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ct.cover_type".
 *
 * @property integer $id
 * @property string $name
 *
 * @property CtCoverTypeOperator[] $ctCoverTypeOperators
 * @property CtOperator[] $operators
 */
class CoverType extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ct.cover_type';
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
    public function getCoverTypeOperators()
    {
        return $this->hasMany(CoverTypeOperator::className(), ['cover_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperators()
    {
        return $this->hasMany(Operator::className(), ['id' => 'operator_id'])->viaTable('cover_type_operator', ['cover_id' => 'id']);
    }
}