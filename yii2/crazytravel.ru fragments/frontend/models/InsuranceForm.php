<?php

namespace frontend\models;

use Yii;

/**
 * Данная модель используется для виджета insurance-form
 */
class InsuranceForm extends \yii\base\DynamicModel{
    
    public $country;
    public $dateStart;
    public $dateEnd;
    public $peopleCount;
    
    public static $maxPeopleSize = 4;

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['country', 'dateStart', 'dateEnd', 'peopleCount'], 'required', 'message' => 'поле обязательно для заполнения'],
            [['country', 'peopleCount'], 'string'],
            ['dateStart', 'date', 'format' => 'd.M.yyyy'],
            ['dateEnd', 'date', 'format' => 'd.M.yyyy'],
            //[['dateStart', 'dateEnd'], 'common\components\validators\DateMinValidator'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'country' => 'Страна',
            'dateStart' => 'Дата начала',
            'dateEnd' => 'Дата окончания',
            'peopleCount' => 'Количество человек',
        ];
    }
}
