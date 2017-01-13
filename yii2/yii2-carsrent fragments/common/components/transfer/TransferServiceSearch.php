<?php

namespace common\components\transfer;

use common\models\lib\Operator;
use common\models\cache\CacheTransfers;
use common\components\SingletonAsyncTaskFactory;
use common\components\transfer\TransferSearchRequest;
use common\components\transfer\TransferServiceProviderFactory;

/**
* Самый неоптимальный поиск - без многопоточности и кеширования
*/
class TransferServiceSearch implements \common\components\iServiceSearch
{
    CONST ASYNCTASKNAME='raw-search-transfers';

    protected function transferClassCompare(array $transferClassOffersA,array $transferClassOffersB)
    {
        $offerA=$transferClassOffersA[0];
        $offerB=$transferClassOffersB[0];
        $basicCmp=self::offersCompare($offerA,$offerB);
        if ($basicCmp)
        {
            return $basicCmp;
        }
        else
        {
            if ($offerA->autoType->passangers == $offerB->autoType->passangers)
            {
                return $offerA->autoType->id > $offerB->autoType->id ? -1:1;
            }
            else
            {
                return $offerA->autoType->passangers > $offerB->autoType->passangers ? -1:1;
            }

        }
    }

    protected function offersCompare(TransferOffer $a, TransferOffer $b)
    {
        if ($a->price->_currency->id==$b->price->_currency->id)
        {
            return ($a->price->_amount > $b->price->_amount) ? -1:1;
        }
        else
        {
            if ($a->price->_amount == $b->price->getLocalized($a->price->_currency->code))
            {
                return 0;
            }
            else
            {
                return $a->price->_amount > $b->price->getLocalized($a->price->_currency->code) ? 1 : -1;
            }

        }
    }

    protected function fetchCached($cached,$cachekey,&$result)
    {
        foreach ($cached as $offer)
        {
            $offer->utime=$cached['rawtime'];
            $result['offers'][$offer->autoType->id][] = $offer;
        }
        $result['trace']['search']['minutime']=min($result['trace']['search']['minutime'],$cached['rawtime']);
        $result['trace']['search']['maxutime']=max($result['trace']['search']['maxutime'],$cached['rawtime']);

        $result['trace']['search'][$cachekey]=[
            'opTrace'=>$cached['trace'],
            'offerCount'=>count($cached['result']),
            'utime'=>$cached['rawtime']
        ];
    }

	static function findOffers($request) {
		$result = [];
		$operators = Operator::getTransferOperators();

		/* получение предложений по всем операторам */
        $asyncSearchTasks=[];

        $result['trace']['search']['startTime'] = microtime(1);
        $result['trace']['search']['offercount'] = 0;
        $result['trace']['search']['minutime'] = INF;
        $result['trace']['search']['maxutime'] = - INF;

        $result['trace']['search']['params']['placeFromId']=$request->placeFrom->id;
        $result['trace']['search']['params']['placeToId']=$request->placeFrom->id;

		foreach ($operators as $transferOperator)
        {
            if (!isset(TransferServiceProviderFactory::$adapters[$transferOperator->id]))
            {
                continue;
            }
            $params=[$transferOperator->id,$request->placeFrom->id,$request->placeTo->id];
			$paramstr=implode(':',$params);
            $cachekey="transfer/searchRaw:{$paramstr}";
            $cached=\Yii::$app->cache->get($cachekey);
			if (!empty($cached))
			{
                self::fetchCached($cached,$cachekey,$result);
			}
            else
            {
                $task = SingletonAsyncTaskFactory::getTask(self::ASYNCTASKNAME . ':' . $paramstr, self::ASYNCTASKNAME, $params);
                if ($task)
                {
                    $asyncSearchTasks[$cachekey] = $task;
                }
            }
		}

        if (!empty($asyncSearchTasks))
        {
            foreach ($asyncSearchTasks as $cachekey => $task) {
                while ($task->running(0, 10000)) {};
                $cached=\Yii::$app->cache->get($cachekey);
                if (!empty($cached))
                {
                    self::fetchCached($cached,$cachekey,$result);
                }
            }
        }

        $result['trace']['search']['offercount'] += count($result['offers']);

        foreach ($result['offers'] as $autoTypeId=>$offers)
        {
            usort($offers,[__CLASS__,'offersCompare']);
        }

        usort($result['offers'],[__CLASS__,'transferClassCompare']);


        if (!empty($result['offers']))
        {
            self::_toCache($request,$result);
        }

        $result['trace']['search']['endTime'] = microtime(1);

		return $result; 
	}

    static function _toCache($request, array $result) {

        $mutexName="toCache:{$request->placeFrom->id}:{$request->placeTo->id}";
        if (!\Yii::$app->mutex->acquire($mutexName,10))
        {
            return;
        }

        $details=self::getDetails($request);
        $minprices=[];
        $batchInsertRows=[];
        $batchUpdaterows=[];

        foreach ($result['offers'] as $autoTypeId=>$offers)
        {
            foreach ($offers as $idx=>$offer)
            {
                $minprices[$offer->autoType->id]=$offer->price->getLocalized('RUB');
                $cachedoffer = CacheTransfers::findOne([
                    'operator' => $offer->operator->operator_id,
                    'key' => $offer->id ]);
                if ($cachedoffer && strtotime($cachedoffer->date_updated) >= $offer->utime) {
                    continue;
                } else {
                    if (!$cachedoffer) {
                        $tocache = new CacheTransfers();
                    } else {
                        $tocache = $cachedoffer;
                    }
                    $tocache->operator = $offer->operator->id;
                    $tocache->local_operator_id = $offer->localOperatorId;
                    $tocache->from_id = $result['trace']['search']['params']['placeFromId'];
                    $tocache->to_id = $result['trace']['search']['params']['placeToId'];
                    $tocache->auto_type_id = $offer->autoType->id;
                    $tocache->currency_id = $offer->price->_currency->id;
                    $tocache->price_orig = $offer->price->_amount;
                    $tocache->price_rub = $offer->price->getLocalized('RUB');
                    $tocache->prepay_orig = $offer->prepay->_amount;
                    $tocache->prepay_rub = $offer->prepay->getLocalized('RUB');
                    $tocache->way = $offer->way;
                    $tocache->comission = $offer->comission;
                    $tocache->key = $offer->id;
                    $tocache->maxpax = $offer->autoType->passangers;
                    if ($idx==0)
                    {
                        $tocache->is_min_price=true;
                    }
                    else
                    {
                        $tocache->is_min_price=false;
                    }
                    if (!$cachedoffer) {
                        $batchInsertRows[] = array_values((array)$tocache->attributes());
                        foreach ($details as $k => $v) {
                            $tocache->$k = $v;
                        }
                    }
                    else
                    {
                        $tocacheattr=(array)$tocache->attributes();
                        $batchUpdaterows[] = array_values($tocacheattr);
                    }
                }
            }
        }

        if (!empty($batchInsertRows) || !empty($batchUpdaterows)) {
            $transaction = Yii::$app->db->beginTransaction();
            try
            {

                CacheTransfers::updateAll(['is_min_price'=>false],['from_id'=>$result['trace']['search']['params']['placeFromId'],'to_id'=>$result['trace']['search']['params']['placeToId']]);

                if (!empty($batchInsertRows)) {
                    Yii::$app->db->createCommand()->batchInsert(CacheTransfers::tableName(), array_keys($tocacheattr), $batchInsertRows)->execute();
                }

                if (!empty($batchUpdaterows)) {
                    Yii::$app->db->createCommand()->batchUpdate(CacheTransfers::tableName(), array_keys($tocacheattr), $batchInsertRows)->execute();
                }

                if (!empty($batchInsertRows) || !empty($batchUpdaterows)) {
                    Yii::$app->db->beginTransaction();
                }


                $transaction->commit();
            }
            catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }
        \Yii::$app->mutex->release($mutexName);
    }

    static function _fillCacheData($request)
    {
        $placeFrom=Place::getPlaceById($request->placeFrom->id);
        $placeFromSynonyms=[];
        foreach ($placeFrom->synonymPlaces as $synplace)
        {
            $placeFromSynonyms[] = $synplace->synonym;
        }
        $placeTo=Place::getPlaceById($request->placeTo->id);
        $placeToSynonyms=[];
        foreach ($placeTo->synonymPlaces as $synplace)
        {
            $placeToSynonyms[] = $synplace->synonym;
        }
        
        $details=[
            'from_name'=>$placeFrom->name,
            'from_name_eng'=>$placeFrom->nameeng,
            'from_country_id'=>$placeFrom->country_id,
            'from_country_name'=>$placeFrom->country_name,
            'from_country_name_eng'=>$placeFrom->country_name_eng,
            'from_country_alias'=>$placeFrom->country_alias,
            'from_code'=>$placeFrom->iata,
            'from_place_alias'=>$placeFrom->alias,
            'from_geotype_id'=>$placeFrom->geotype,
            'from_geotype_name'=>$placeFrom->geotype_name,
            'from_geotype_name_eng'=>$placeFrom->geotype_name_eng,
            'from_query_name'=>implode('|',$placeFromSynonyms),

            'to_name'=>$placeTo->name,
            'to_name_eng'=>$placeTo->nameeng,
            'to_country_id'=>$placeTo->country_id,
            'to_country_name'=>$placeTo->country_name,
            'to_country_name_eng'=>$placeTo->country_name_eng,
            'to_country_alias'=>$placeTo->country_alias,
            'to_code'=>$placeTo->iata,
            'to_place_alias'=>$placeTo->alias,
            'to_geotype_id'=>$placeTo->geotype,
            'to_geotype_id'=>$placeTo->geotype,
            'to_geotype_name'=>$placeTo->geotype_name,
            'to_geotype_name_eng'=>$placeTo->geotype_name_eng,
            'to_query_name'=>implode('|',$placeToSynonyms)
        ];


        /* TODO
         *
            * @property boolean $is_hotels_from
            * @property boolean $is_hotels_to
            * @property boolean $is_streets_from
            * @property boolean $is_streets_to
            * @property integer $popularity_route_order
            * @property integer $popularity_route_search
            * @property double $popularity_route_weight_kiwi
            * @property integer $popularity_from_order
            * @property integer $popularity_from_search
            * @property integer $popularity_from_weight_kiwi
            * @property integer $popularity_to_order
            * @property integer $popularity_to_search
            * @property integer $popularity_to_weight_kiwi
         */
    }
}