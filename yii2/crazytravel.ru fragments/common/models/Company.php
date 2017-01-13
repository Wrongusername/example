<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ct.company".
 *
 * @property integer $id
 * @property string $name
 * @property string $code
 *
 * @property CtCompanyOperator[] $ctCompanyOperators
 */
class Company extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ct.company';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'string', 'max' => 50],
            [['code'], 'string', 'max' => 10],
            [['url'], 'string', 'max' => 50],
	    [['enabled'], 'boolean']
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
            'code' => 'Code',
	    'url' => 'URL',
	    'enabled' => 'Enabled'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyOperators()
    {
        return $this->hasMany(CompanyOperator::className(), ['company_id' => 'id']);
    }
}