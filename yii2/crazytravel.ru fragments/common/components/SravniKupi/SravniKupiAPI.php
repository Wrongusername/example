<?php

namespace common\components\SravniKupi;

use \Yii;
use \common\components\UrlRequest\UrlRequest;
use \common\models\Operator;
use \common\models\Country;
use \common\models\CountryOperator;
use \common\models\RiskType;
use \common\models\RiskTypeOperator;
use \common\models\CoverType;
use \common\models\CoverTypeOperator;
use \common\models\Company;
use \common\models\CompanyOperator;
use \common\models\MedProgram;
use \common\components\CurrencyRates;
use \common\components\SravniKupi\ScCacheNs;
use \common\components\SravniKupi\ScCacheGo;
use \common\components\SravniKupi\ScCacheLugg;



use \common\models\CountryAmount;

class SravniKupiAPI {
    protected $login = 'x';
    protected $password = 'x';
    protected $apiURL = 'x';
    protected $bronApiUrl = 'x';
    
    //Базовый Url для формирования фрейма оплаты
    protected $basePayFrameUrl = "x";
    protected $partnerId = 5;
    protected $partnerPassword = 'x';
    protected $partnerSecretKey = 'x';
    
    protected static $opid;
    public static $staticInit;
    public static $risks;
    public static $riskMapTo=[];
    public static $riskMapFrom=[];
    public static $covers;
    public static $coverMapTo=[];
    public static $coverMapFrom=[];
    public static $companies;
    public static $companyMapTo=[];
    public static $companyMapFrom=[];

    public static $rates;
   
    protected static $ccache=[
		'1'=>'\common\components\SravniKupi\ScCacheLugg',
		'2'=>'\common\components\SravniKupi\ScCacheNs',
		'3'=>'\common\components\SravniKupi\ScCacheGo'
    ];

    public function getMedDescription($med_covers)
    {
	$description='';
	foreach ($med_covers as $k=>$c)
	{
		if ($k>0)
			$description .= '; ';
		$description .= (isset(self::$med_list[$c]['description']) ? self::$med_list[$c]['description'] : self::$med_list[$c]['name']);
	}
	$description=mb_substr($description, 0, 1) . mb_strtolower(mb_substr($description, 1));
	return $description;
    }

    public function __construct()
    {
	if (self::$staticInit)
		return;


	$crates=new CurrencyRates();
	self::$rates=$crates->rates;
	mb_internal_encoding("UTF-8");

        self::$opid=Operator::find()->where("code='sravni_kupi'")->select('id')->asArray()->one()['id'];

	self::$risks=RiskTypeOperator::find()
            ->select([
                    'r.id',
                    'r.name',
                    'rto.operator_risk_id',
                    'rto.risk_id',
		    'rto.operator_id'
                ])
            ->from(['rto' => RiskTypeOperator::tableName()])
            ->leftJoin(['r' => 'ct.risk_type'], 'r.id = rto.risk_id')
            ->where('rto.operator_id=:opid',[':opid'=>self::$opid])->asArray()->all();

	foreach(self::$risks as $r)
	{
		self::$riskMapTo[$r['id']]=$r['operator_risk_id'];
		self::$riskMapFrom[$r['operator_risk_id']]=$r['id'];
	}


	self::$covers=CoverTypeOperator::find()
            ->select([
                    'c.id',
                    'c.name',
                    'cto.operator_cover_id',
                    'cto.cover_id',
		    'cto.operator_id'
                ])
            ->from(['cto' => CoverTypeOperator::tableName()])
            ->leftJoin(['c' => 'ct.cover_type'], 'c.id = cto.cover_id')
            ->where('cto.operator_id=:opid',[':opid'=>self::$opid])->asArray()->all();

	foreach(self::$covers as $c)
	{
		self::$coverMapTo[$c['id']]=$c['operator_cover_id'];
		self::$coverMapFrom[$c['operator_cover_id']]=$c['id'];
	}

	self::$companies=CompanyOperator::find()
            ->select([
                    'c.id',
                    'c.name',
		    'c.code',
                    'co.operator_company_id',
                    'co.company_id',
		    'co.operator_id'
                ])
            ->from(['co' => CompanyOperator::tableName()])
            ->leftJoin(['c' => 'ct.company'], 'c.id = co.company_id')
            ->where('co.operator_id=:opid and c.enabled is true and co.enabled is true',[':opid'=>self::$opid])->asArray()->all();

	foreach(self::$companies as $c)
	{
		self::$companyMapTo[$c['id']]=$c['operator_company_id'];
		self::$companyMapFrom[$c['operator_company_id']]=$c['id'];
	}

    }
    protected function formatCT(&$p)
    {
      $p['login']=$this->login;
      if (!isset($p['product_id']))
	$p['product_id']=4;
      if (!isset($p['type']))
	$p['type']=1;
      //в настройках для локальной машины можно указывать свои параметры
      $timeDelay = isset(Yii::$app->params['sravniKupiSyncRequestTime']) ? Yii::$app->params['sravniKupiSyncRequestTime'] : 0;
      $p['time']=time() + $timeDelay;
      $p['key']=md5($this->login . $this->password . $p['time'] . $p['product_id'] . $p['type']);
      if (isset($p['param']))
        $p['param']=json_encode($p['param']);
    }
    
    public function getCompanySpheres()
    {
        return $this->apicall('/company_sphere',
        [
         'product_id'=>0,
    	 'type'=>1
    	]);
    }
    
    public function validateQuote(array &$quote,array &$errors)
    {
        foreach (['country_id','date_from','date_to'] as $k)
            if (!isset($quote[$k]))
                throw new UserException("Отсутствует обязательный параметр массива quote : $k");

        if ($quote['date_from']<=time())
                $errors[]="Дата начала полиса должна быть больше текущей";
                
        if ($quote['date_to']<=$quote['date_from'])
                $errors[]="Дата окончания полиса должна быть больше даты начала";

	if (count($errors)==0)
	$quote['period']=ceil(($quote['date_to']-$quote['date_from'])/60/60/24);
	$quote['date_from']=$this->dateFormat($quote['date_from']);
	$quote['date_to']=$this->dateFormat($quote['date_to']);
    }
    
    public function validateTourist(array $tourist,array &$errors)
    {
        foreach (['nationality','date_birth'] as $k)
            if (!isset($tourist[$k]))
                throw new UserException("Отсутствует обязательный параметр массива tourist : $k");
    }

    public function opCountry($ourCountry)
    {
	return CountryOperator::find()->where('operator_id=:opid and country_id=:cid',[':opid'=>self::$opid,':cid'=>$ourCountry])->select('operator_country_id')->asArray()->one()['operator_country_id'];
    }

    public function opRisk($ourRisk)
    {
	return RiskTypeOperator::find()->where('operator_id=:opid and risk_id=:rid',[':opid'=>self::$opid,':rid'=>$ourRisk])->select('operator_risk_id')->asArray()->one()['operator_risk_id'];	
    }

    public function opCovers()
    {
	return CoverTypeOperator::find()->where('operator_id=:opid', [':opid'=>self::$opid])->select('operator_cover_id')->asArray()->all();
    }

    public function dateFormat($time)
    {
	return date('Y-m-d',$time);
    }
    public function getCurrentCoverPrice($coverID,$company,$amount,$currency_id,$period)
    { 

	$cache=self::$ccache[$coverID];
	$cc=$cache::$param;
	$cp=$cc[$company];

	foreach ($cp as $cache_amount=>$periods)
	{
		if ($amount>=$cache_amount)
		{
			$nearest_amount=$periods;
			break;
		}
	}

		if (!$nearest_amount)
			$nearest_amount=end($cp);
	
		foreach ($nearest_amount as $cache_period=>$cache_data)
		{
			if ($period>=$cache_period)
				$current=$cache_data;
		}
	
		if (!$current)
			$current=end($nearest_amount);
	
		$current['price']*=self::$rates[$currency_id];
	return $current;
    }

    public function getCoverPrices($coverID,$company,$currency_id)
    {
	$cache=self::$ccache[$coverID];
	$cc=$cache::$param;
	$cp=$cc[$company];

	foreach($cp as &$cperiod)
		foreach($cperiod as &$camount)
			$camount['price']*=self::$rates[$currency_id];
	return $cp;	
    }    

    public function calculateVZR(array $p, $isUpdate=false)
    {
        $errors=[];
        if (!is_array($p['quote']))
            throw new UserException('Отсутствует обязательный массив параметров quote');
        if (!is_array($p['tourist']))
            throw new UserException('Отсутствует обязательный массив параметров tourist');
        $this->validateQuote($p['quote'],$errors);
        foreach ($p['tourist'] as $tourist)
            $this->validateTourist($tourist,$errors);

        if (count($errors)>0)
            return $errors;


        
        if (!isset($p['companies']))
        {
            $clist=CountryAmount::find(['country_id=:cid','operator_id=:opid'], [':cid' => $p['quote']['country_id'],':opid'=>self::$opid])->select('company_id')->asArray()->distinct()->all();
            $p['companies']=[];
            foreach($clist as $comp)
            {
                if (!isset(self::$companyMapFrom[$comp['company_id']])) //company not enabled or not exists
                    continue;
                else
                    $p['companies'][]=['company_id'=>$comp['company_id']];
            }
        }

	if (!isset($p['quote']['amount']))
		$p['quote']['amount'] = 0;

	$p['quote']['policy_type'] = 1; //одноразовый
	$p['quote']['country_id']=$this->opCountry($p['quote']['country_id']);

	if (!isset($p['quote']['risk_id']))
		throw new UserException('Отсутствует обязательный параметр [quote][risk_id]');
	else
		$risk=self::$riskMapTo[$p['quote']['risk_id']];
	
	if (!is_array($p['covertypes']))
		throw new UserException('Отсутствует обязательный массив параметров [covertypes]');
	else
		foreach($p['covertypes'] as &$c)
			$c['cover_id']=self::$coverMapTo[$c['cover_id']];

        $req=[
                'product_id'=>4,
                'type'=>1,
                'param'=>[
                            'quote'=>$p['quote'],
                            'company'=>$p['companies'],
                            'cover'=>$p['covertypes'],
                            'tourist'=>$p['tourist']
                         ]
             ];

        $res=$this->apicall('calc',$req,false,true);

	if ($res->status!=1)
		return;

	$offers=[];

	if (count($res->results)==0)
		return;

	foreach ($res->results as $ccode=>$company)
	{
                if (!isset(self::$companyMapFrom[$company->company_id])) //company not enabled or not exists
                    continue;

		if ($company->status!=1)
			continue;

		if (count($company->offers)==0)
			continue;

		foreach($company->offers as $offer)
		{
			

			$o=[
				'response_id' => $company->response_id,
				'offer_id' => $offer->offer->offer_id,
				'policy_list' => $offer->policy_list,
				'program_name' => $offer->offer->name,
				'program_description' => $offer->offer->description,
				'med_program_id' => $offer->offer->med_program->id,
				'med_program_name' => $offer->offer->med_program->name,
				'company_id' => self::$companyMapFrom[$company->company_id],
				'company_code' => $ccode,
				'price'=>$offer->price->full,
				'price_currency'=>$offer->currency,
				'amount'=>$offer->additional->amount,
				'amount_currency'=>$offer->additional->currency,
				'operator_id'=>self::$opid,
				'cover'=>[]
			];

			if (!$isUpdate)
			{
				$ppd=array_sum($o['price'])/count($p['tourist']);
				$dailyCoverPrice=0;
				$flatCoverPrice=0;
			}
			foreach ($offer->cover as $cover)
			{
				if ($cover->status!='Y')
					continue;
				$ourcid=self::$coverMapFrom[$cover->id];
				$o['cover'][$ourcid]=$cover->amount;

				if (!$isUpdate)
				{
					$o['coverprice'][$ourcid]=$this->getCurrentCoverPrice($ourcid,$ccode,$cover->amount,$o['amount_currency'],$p['quote']['period']);
		
					if ($o['coverprice'][$ourcid]['type']=='trip')
						$flatCoverPrice+=$o['coverprice'][$ourcid]['price'];
					elseif($o['coverprice'][$ourcid]['type']=='day')
						$dailyCoverPrice+=$o['coverprice'][$ourcid]['price'];
				}
			}

			if (!$isUpdate)
			{
				for ($i=1;$i<=3;$i++)
					$o['coverprices'][$i]=$this->getCoverPrices($i,$o['company_code'],$o['amount_currency']);

				$o['dailyCoverPrice'] = $dailyCoverPrice;
				$o['flatCoverPrice'] = $flatCoverPrice;
				$o['prePpd']=$ppd;
				$ppd=($ppd - $flatCoverPrice)/$p['quote']['period'] - $dailyCoverPrice;
				$o['ppd']=$ppd;
			}

			$o['med_description']=MedProgram::find()->where([ 'operator_id'=>self::$opid,
									  'operator_company_id'=>$company->company_id,
									  'med_program_id'=>$offer->offer->med_program->id
									])->select('description')->asArray()->one()['description'];
		

			$offers[]=$o;
		}
		

	}
	return [
			'operator_id'=>self::$opid,
			'offers'=>$offers
		];
    }

    public function updateCachedOffer(&$model)
    {
	$param = [
				'quote' => [
					        'amount' => $model['amount'],
						'country_id' => $model['country'],
						'risk_id' => $model['riskType'],
						'date_from' => strtotime($model['dateStart']),
						'date_to' => strtotime($model['dateEnd'])
				],
				'companies'=>[
							[
								'company_id'=>self::$companyMapTo[$model['company_id']]
							]
						],
			 	'covertypes'=>[],
			 	'tourist' => []
		];
	for ($i=0;$i<$model['peopleCount'];$i++)
	{
		$n="nationality$i";
		$b="birthday$i";
		$param['tourist'][]=[
		'date_birth'=>$this->dateFormat(strtotime($model[$b])),
		'nationality'=>$model[$n]
		];
	}

	foreach(self::$coverMapTo as $ourid=>$theirid)
	{
		$cname = 'cover' . $ourid;
		if ((isset($model[$cname])) && ($model[$cname]>0) )
			$param['covertypes'][]=[
							'cover_id' => $theirid,
                                			'amount' => $model[$cname]
						];
	}

	$res=$this->calculateVZR($param,true);

	if (isset($res['offers']) == false || count($res['offers'])==0)
		return;

	foreach($res['offers'] as $o)
	{
		if ($o['offer_id']==$model['offer_id'])
		{
			$updated=true;
			foreach ($o['price'] as $k=>$p)
				$model['price' . $k]=$p;

			foreach ($o['policy_list'] as $k=>$p)
				$model['policy' . $k]=$p;

			for ($i=0;$i<=5;$i++)
			{
				$cname='cover' . $i;
				if (isset($o['cover'][$i]))
				{
					$model[$cname]=$o['cover'][$i];
				}
				else 
					unset($model[$cname]);
			}

			$model['response_id']=$o['response_id'];
			$model['operator_id']=self::$opid;
			
			break;	
		}
	}
	unset($model['cache']);
	return $updated;
    }
    
    public function getVZRCompanies()
    {
        return $this->getCompaniesBySphere(1,4);
    }
    
    public function getVZRCovers()
    {
        return $this->getCover(4);        
    }
    
    public function getVZROffers()
    {
        return $this->getCompanyOffers(4);           
    }
    
    public function getCompaniesBySphere($sphere_id,$product_id)
    {
        return $this->apicall("/company/$sphere_id/$product_id",
        [
         'product_id'=>0, //$product_id //??
    	 'type'=>1
    	 ]);
    }

    public function getCurrencyTypes()
    {
        return $this->apicall("/currency",
        [
         'product_id'=>0, //$product_id //??
    	 'type'=>1
    	 ]);
    }
    
    public function getCompanyOffers($product_id)
    {
        return $this->apicall("/company_offer/$product_id",
        [
         'product_id'=>0, //$product_id //??
    	 'type'=>1
    	 ]);
    }
    
    public function getProducts()
    {
        return $this->apicall("/product",
        [
         'product_id'=>0,
    	 'type'=>1
    	 ]);        
    }
    
    public function getCountries()
    {
        return $this->apicall("/country",
        [
         'product_id'=>0,
    	 'type'=>1
    	 ]);
    }
    
    public function getCountryGroups()
    {
        return $this->apicall("/country_group",
        [
         'product_id'=>0,
    	 'type'=>1
    	 ]);
    }
    
    public function getCountryAmount()
    {
        return $this->apicall("/country_amount",
        [
         'product_id'=>0,
    	 'type'=>1
    	 ]);
    }

    public function getMedicalProgram()
    {
        return $this->apicall("/medical_program ",
        [
         'product_id'=>0,
    	 'type'=>1
    	 ]);
    }
    
    public function getCover($product_id)
    {
        return $this->apicall("/cover/$product_id",
        [
         'product_id'=>0, //$product_id //??
    	 'type'=>1
    	 ]);
    }

    public function getRisk()
    {
        return $this->apicall("/risk",
        [
         'product_id'=>0,
    	 'type'=>1
    	 ]);
    }
    
    public function getServerTime()
    {
        return $this->apicall("/server_time",
        [
         'product_id'=>0,
    	 'type'=>1
    	 ]);
    }      
    
    public function apicall($service,$p, $asAssocArr = false, $isBronApi = false) {
        
        if($isBronApi == true) {
            $url = $this->bronApiUrl . $service;
        } else {
            $url = $this->apiURL . $service;
        }

        $this->formatCT($p);

        $res = UrlRequest::post($url, $p);

        return json_decode($res,$asAssocArr);
    }

}