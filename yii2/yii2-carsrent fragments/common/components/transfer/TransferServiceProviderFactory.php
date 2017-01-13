<?php

namespace common\components\transfer;
use common\components\iServiceProviderFactory;
use common\models\lib\Operator;

/**
* 
*/
class TransferServiceProviderFactory implements iServiceProviderFactory
{
    //TODO переделать через glob или задавать адаптеры в BD?
	public static $adapters = [
        2 => '\kiwi\KiwiDeepServiceProvider',
        6 => '\iway\IwayServiceProvider',
        7 => '\autoeurope\AutoEuropeServiceProvider',
        10 => '\local\LocalServiceProvider'
    ];
    CONST BASEPATH='\common\components\adapters';

	public static function getAdapter(Operator $operator) {
        if (isset(self::$adapters[$operator->id])) {
            $adapterFilePath = self::BASEPATH . self::$adapters[$operator->id];
            return new $adapterFilePath($operator);
        }
	}

    public static function getAllAdapters($active) {
        $adapters = [];

        $operatorsQuery = Operator::find()->where(['id' => array_keys(self::$adapters), 'transfers'=>true]);
        if ($active) {
            $operatorsQuery->andWhere(['active' => true]);
        }
        $operators = $operatorsQuery->asArray()->all();

        foreach ($operators as $op) {
            $adapters[] = self::getAdapter($op);
        }

        return $adapters;
    }
}