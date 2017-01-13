<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ct.currency".
 *
 * @property integer $id
 * @property string $code
 * @property double $rate
 */
class Currency extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ct.currency';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'code', 'rate'], 'required'],
            [['id'], 'integer'],
            [['rate'], 'number'],
            [['code'], 'string', 'max' => 4]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'rate' => 'Rate',
        ];
    }
}