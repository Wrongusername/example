<?php


namespace console\controllers;
use yii\console\Controller;

use yii\mutex\FileMutex;
use common\components\transfer\TransferServiceProviderFactory;
use common\components\transfer\TransferSearchRequest;
use common\models\lib\Operator;

class AsyncTaskController extends Controller
{

    public $mutexname;

    public function options($actionID) {
        return array_merge(parent::options($actionID),
           ['mutexname']
        );
    }
    
    public function beforeAction($action)
    {
        if (!empty($this->mutexname))
        {
                if (!\Yii::$app->mutex->acquire($this->mutexname))
                {
                    echo 0;
                    return false;
                }
                else
                {
                    echo 1;
                }
        }
        else
        {
            echo 1;
        }
        return parent::beforeAction($action);
    }

    public function afterAction($action, $result)
    {
        if (!empty($this->mutexname))
        {
            \Yii::$app->mutex->release($this->mutexname);
        }
        return parent::afterAction($action, $result);
    }

    public function actionRawSearchTransfers($operatorId,$placeFromId,$placeToId)
    {
        $searchParams=['placeFromId'=>$placeFromId,'placeToId'=>$placeToId];
        $transferSearchRequest=new TransferSearchRequest($searchParams);

        if (!$transferSearchRequest->validate()) {
            $offers=[];
        }
        else
        {
            $operator=Operator::findOne($operatorId);
            if (!$operator)
            {
                $offers=[];
            }
            else
            {
                $trace=[];
                $adapter = TransferServiceProviderFactory::getAdapter($operator);
                $offers = $adapter->getOffers($transferSearchRequest,$trace);
            }
        }

        \Yii::$app->cache->set("transfer/searchRaw:$operatorId:$placeFromId:$placeToId",['rawtime'=>time(),'result'=>$offers,'trace'=>$trace],60);
    }

    public function actionSearchTest($operatorId,$placeFromId,$placeToId)
    {
        $sleeprand=mt_rand(5,15);
        sleep($sleeprand);
        \Yii::$app->cache->set("transfer/searchRaw:$operatorId:$placeFromId:$placeToId","test:$operatorId:$placeFromId:$placeToId:$sleeprand",60);
    }

}