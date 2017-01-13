<?php

namespace frontend\models;

use Yii;

/**
 * Данная модель используется для seach-form
 */
class InsuranceSearchForm extends InsuranceForm {
    
    /*public $country;
    public $dateStart;
    public $dateEnd;
    public $peopleCount;
    public static $maxPeopleSize = 4;*/
    
    public $nationality0;
    public $nationality1;
    public $nationality2;
    public $nationality3;
    
    public $birthday0;
    public $birthday1;
    public $birthday2;
    public $birthday3;

    public $riskType; 
    public $coverFilter1;
    public $coverFilter2;
    public $coverFilter3;
    public $coverFilter4;
    public $coverFilter5;

    /**
     * @inheritdoc
     */
    public function rules() {
        //'country', 'dateStart', 'dateEnd', 'peopleCount',
        //'country', 'peopleCount',
        //'dateStart', 'dateEnd',

        $r=array_merge(parent::rules(), 
            [
                [['riskType','coverFilter1','coverFilter2','coverFilter3','coverFilter4','coverFilter5'], 'string'],
                ['birthday0', 'date', 'format' => 'dd.MM.yyyy', 'when' => function($model) { return $model->peopleCount > '0';}],
                ['birthday1', 'date', 'format' => 'dd.MM.yyyy', 'when' => function($model) { return $model->peopleCount > '1';}],
                ['birthday2', 'date', 'format' => 'dd.MM.yyyy', 'when' => function($model) { return $model->peopleCount > '2';}],
                ['birthday3', 'date', 'format' => 'dd.MM.yyyy', 'when' => function($model) { return $model->peopleCount > '3';}],
                /*['date_start', 'date', 'format' => 'dd.MM.yyyy'],
                ['date_end', 'date', 'format' => 'dd.MM.yyyy'],*/
            ]
	
        );

	return $r;
    }
    public function updateRules($pcount)
    {	
		$b=[];
		$n=[];
		for ($i=0;$i<$pcount;$i++)
		{
			$b[]="birthday$i";
			$n[]="nationality$i";
		}
		$this->addRule($b,'date',['format'=>'dd.MM.yyyy']);
		
		$this->addRule($b,'required');
		$this->addRule($n,'required');
		$this->addRule($n,'string');
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        
        return array_merge(parent::attributeLabels(), [
            
                'nationality0' => 'nationality0',
                'nationality1' => 'nationality1',
                'nationality2' => 'nationality2',
                'nationality3' => 'nationality3',
                'birthday0' => 'День рождения',
                'birthday1' => 'День рождения',
                'birthday2' => 'День рождения',
                'birthday3' => 'День рождения',
                'riskType' => 'Тип риска',
                'coverFilter1' => 'Покрытие багажа',
                'coverFilter2' => 'Покрытие несчастных случаев',
                'coverFilter3' => 'Покрытие гражд. отв.',
                'coverFilter4' => 'Покрытие квартиры',
                'coverfilter5' => 'Покрытие отмены поездки'
				
            ]
        );
    }
}
