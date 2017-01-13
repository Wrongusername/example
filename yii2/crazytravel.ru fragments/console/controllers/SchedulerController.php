<?
namespace console\controllers;
use \Yii;
use \common\models\Country;
use \common\models\Cache;
use \common\models\Currency;
use \common\components\SravniKupi\SravniKupiAPI;


class SchedulerController extends \yii\console\Controller
{

public $numCached=0;
protected static $rates=[];

public function init()
{
}


public function actionCalcCache(){

	$currencies=Currency::find()->asArray()->all();
	foreach ($currencies as $c)
	{
		self::$rates[$c['id']]=$c['rate'];
	}

	$scapi=new SravniKupiAPI();
	$countries=Country::findBySql('select c.id, c.name_ru from ct.country c where id in (select distinct co.country_id from ct.country_amount ca left join ct.country_operator co on co.operator_country_id = ca.country_id and co.operator_id = ca.operator_id) and c.active=true order by c.id')->asArray()->all();

	$date = new \DateTime();
	$date->modify('+1 month');
	$date->setTime(12,0,0);
	
	$d = $date->format('d');
	$m = $date->format('m');
	$Y = $date->format('Y');
	
	$date->setDate($Y , $m , 3);
	$dateStart=$date->getTimestamp();
	$date->setDate($Y , $m , 10);
	$dateEnd=$date->getTimestamp();

	$param = array(
	'quote' => array(
		'risk_id' => 1,
		'date_from' => $dateStart,
		'date_to' => $dateEnd
	),
	 'covertypes'=>[],
	 'tourist' => array(
		0 => array(
			'nationality' => 1,
			'date_birth' => '1985-07-20'
			)
		)
	);
	foreach ($countries as $cnt)
	{
		$param['quote']['country_id']=$cnt['id'];
		$res=$scapi->calculateVZR($param);
		var_dump($res);
		if ($res && $res['offers'])
			$this->toCache($param,$res);
	}
	Cache::deleteAll('utime < :utime',[':utime'=>date('d.m.Y 00:00:00',strtotime('-1week'))]);
}

    public function toCache($request,$res)
    {
	var_dump("caching country_id {$request['quote']['country_id']} offers");
	foreach($res['offers'] as $o)
	{
		$ppd=$o['price'][0]/7/self::$rates[$o['amount_currency']];
		if (!($cachedOffer=Cache::find()->where([
				      'offer_id' => $o['offer_id'],
				      'country_id'=>$request['quote']['country_id'],
				      'company_id'=>$o['company_id'],
				      'amount'=>$o['amount'],
				      'med_program_id'=> $o['med_program_id']
				])->one()))
		{
			$cachemodel=new Cache([
						    'operator_id'=>1,
				  		    'offer_id' => $o['offer_id'],
				 		    'country_id'=>$request['quote']['country_id'],
						    'company_id'=>$o['company_id'],
						    'amount'=>$o['amount'],
						    'amount_currency_id'=>$o['amount_currency'],
						    'price_per_day'=>$ppd,
						    'med_program_id'=>$o['med_program_id'],
						    'utime'=>date('d-m-Y H:i:s',time())
					      ]);
			$cachemodel->save();
			$this->numCached++;
			var_dump($this->numCached);
		}
		else
		{
			$cachedOffer->operator_id=1;
			$cachedOffer->price_per_day=$ppd;
			$cachedOffer->utime=date('d-m-Y H:i:s',time());
			$cachedOffer->update();	
		}
	}
   }

   public function actionUpdateRates()
   {
	$currency=Currency::find()->all();
	$cb=(array)new \SimpleXMLElement(@file_get_contents('http://www.cbr.ru/scripts/XML_daily.asp'));
	foreach ($currency as $ourC)
	{

		if ($ourC->code=='RUB')
			continue;

		foreach ($cb['Valute'] as $cbC)
		{

			if (current($cbC->CharCode)==$ourC->code)
			{
				$ourC->rate=current($cbC->Value)/current($cbC->Nominal);
				$ourC->update();
			}	
		}
	}
	return 0;
   }

}