<?php

namespace common\models;

use Yii;
use \common\models\gate\CountryGateInfo;

/**
 * This is the model class for table "ct.country".
 */
class Country extends \yii\db\ActiveRecord {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'ct.country';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code', 'name_ru', 'name_en'], 'required'],
            [['group_id'], 'integer'],
            [['date_create'], 'safe'],
            [['active'], 'boolean'],
            [['code'], 'string', 'max' => 16],
            [['name_ru', 'name_en'], 'string', 'max' => 50],
            [['code'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'name_ru' => 'Name Ru',
            'name_en' => 'Name En',
            'group_id' => 'Group ID',
            'date_create' => 'Date Create',
            'active' => 'Active',
        ];
    }
    
    /**
     * Запрос на получение списка стран, для которых нет соотвествия в таблице country_gate_info
     */
    public static function getCountryIdListExceptCountryGetInfo() {
        
        $countriesQuery = CountryGateInfo::find()
            ->select('id');
        
        $countryListQuery = self::find()
            ->select("id, name_ru")
            ->where([
                'not in ', 'id', $countriesQuery
            ]);
            
        return $countryListQuery->asArray()->all();
/*
SELECT *
FROM ct.country
WHERE id not in (SELECT id FROM ct.country_gate_info)
*/
    }
}
