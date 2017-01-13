<?
namespace Yandex;
require_once 'models/Route.php';
require_once 'inc/wordCases.php';
require_once 'inc/search.class.php';

class AdGen
{
	CONST LINKBASE='http://carsrent.ru';
	CONST MAX_AD_BASIC_TITLE_LENGTH=33;
	CONST MAX_AD_EXTENDED_TITLE_LENGTH=52;
	CONST MAX_KEYWORD_WORDS_COUNT=7;
	CONST MAX_AD_BODY_LENGTH=75;
	CONST MAX_AD_TITLE_WORD_LENGTH=22;
	CONST MAX_AD_BODY_WORD_LENGTH=23;
	CONST MAX_SITELINK_LENGTH=30;
	CONST MAX_SITELINKS_TOTAL_LENGTH=66;
	CONST MAX_SITELINK_PRICE_RUB=4000;
	public static $abbrev_repl_from=['/аэропорт*\b/iu'];
	public static $abbrev_repl_to=['а/п'];
	public static $types=['трансфер','такси'];
	public static $currencysymbols=[
			'RUB'=>'р',
			'USD'=>'$',
			'EUR'=>'€'
		  ];
	protected static $bestroutes=[];
	
	public $maxAds;
	public $numAds=0;
	public $ordertobidratio;
	public $minbid;
	public $maxbid;
	protected $phraseTemplates;
	protected $mainTextTemplates;
	protected $linksCache;
	protected $qlinksets;
	protected $dryrun;
	protected $log;
	protected $ignoreabort;
	protected $adKeys;
	protected $bids;
	protected $keywords;
	protected static $hfuplace;
	protected $bidcreatelinks;

	public function __construct($conditions,$ptpls,$mtpls,$maxAds=1000,$dryrun=false,$log=false,$ignoreabort=false,$ordertobidratio,$minbid,$maxbid,$maxgeo,$geooffset)
	{
		$this->maxAds=$maxAds;
		$this->dryrun=$dryrun;
		$this->log=$log;
		$this->ignoreabort=$ignoreabort;
		if ($ignoreabort)
		{
			ignore_user_abort(true);
			set_time_limit(0);
		}
		
		if ((int)$this->maxAds<=0)
		{
		    $this->log=true;
		    $this->logmsg('Недопустимое число объявлений');
			throw new \Exception('Недопустимое число объявлений');
		}
		if (!$ordertobidratio || !$minbid || !$maxbid)
		{
		    $this->log=true;
		    $this->logmsg('Не заданы параметры ставок');
		    throw new \Exception('Не заданы параметры ставок');
		}
		$this->ordertobidratio=$ordertobidratio;
		$this->minbid=$minbid;
		$this->maxbid=$maxbid;
		
		if (!count($ptpls))
		{
		    $this->log=true;
		    $this->logmsg('Не заданы шаблоны фраз');
			throw new \Exception('Не заданы шаблоны фраз');
		}
		$this->phraseTemplates=$ptpls;
		
		if (!count($mtpls))
		{
		    $this->log=true;
		    $this->logmsg('Не заданы шаблоны объявлений.');
			throw new \Exception('Не заданы шаблоны объявлений.');
		}
		$this->mainTextTemplates=$mtpls;
		
		self::$hfuplace=\db::loadby('alias','SELECT id, COALESCE(attr(id, 502681), attr(id, 155092)) as alias FROM lib WHERE lib=2100 AND NOT attr(id, 106888)::integer IN(2202)');
		$this->bidcreatelinks=\db::loadby('key',"select link1_title as title, link1_href as href, from_id || ':' || to_id || ':' || group_template_id as key from yandex.vw_advertisment");

		$this->keywords=\db::loadby('keywords','select distinct keywords from yandex.ya_ad_group');
		foreach(\db::load('select * from yandex.ya_sitelink') as $ql)
		{
			foreach ($ql as $k=>$f)
			{
				$ql->$k=self::mb_trim($f);
				if (empty($f))
				{
					$ql->$k=null;
				}
			}
			$paramsQlinkSet=[
					'link1_title'=>@$ql->link1_title,
					'link1_href'=>@$ql->link1_href,
					'link2_title'=>@$ql->link2_title,
					'link2_href'=>@$ql->link2_href,
					'link3_title'=>@$ql->link3_title,
					'link3_href'=>@$ql->link3_href,
					'link4_title'=>@$ql->link4_title,
					'link4_href'=>@$ql->link4_href,

			];
			$this->qlinksets[sha1(implode(' ',array_values($paramsQlinkSet)))]=$ql->id;
		}
		
		$this->adKeys=\db::loadby('key',"select ygrp.from_id || ':' || ygrp.to_id || ':' || ygrp.group_template_id as key from yandex.ya_ad_group ygrp join yandex.ya_ad yad on yad.group_id = ygrp.id");		
		self::objArrNameSortDesc($this->mainTextTemplates);
		self::objArrNameSortDesc($this->phraseTemplates);

		$this->bids=\db::loadby('key',"select *, from_id || ':' || to_id as key from yandex.ya_bid");
				
		$geoCache=
			[
					'fromCountry'=>\db::load('select distinct from_country_id,from_country_text,max(id) as cache_id,
															  max(from_country_popularity_order) as from_country_popularity_order,
															  max(from_country_popularity_search) as from_country_popularity_search,
															  max(from_country_popularity_weight_kiwi) as from_country_popularity_weight_kiwi 
															 from yandex.ya_cache_update' . $conditions . ' group by from_country_id,from_country_text
															 order by from_country_popularity_search desc nulls last, from_country_popularity_order desc nulls last, from_country_popularity_weight_kiwi desc nulls last limit ' . (int)$maxgeo) . ' offset ' . (int)$geooffset,
					'from'=>\db::load('select distinct from_id,from_text,from_geo_type,max(id) as cache_id,
        									   max(from_popularity_order) as from_popularity_order,
        									   max(from_popularity_search) as from_popularity_search,
        									   max(from_popularity_weight_kiwi) as from_popularity_weight_kiwi
        									 from yandex.ya_cache_update' . $conditions . ' group by from_id,from_text,from_geo_type
        									 order by from_popularity_search desc nulls last, from_popularity_order desc nulls last, from_popularity_weight_kiwi desc nulls last limit ' . (int)$maxgeo . ' offset ' . (int)$geooffset),
					'toCountry'=>\db::load('select distinct to_country_id,to_country_text,max(id) as cache_id, 
											 max(to_country_popularity_order) as to_country_popularity_order,
										     max(to_country_popularity_search) as to_country_popularity_search,
											 max(to_country_popularity_weight_kiwi) as to_country_popularity_weight_kiwi 
											from yandex.ya_cache_update' . $conditions . ' group by to_country_id,to_country_text
					                        order by to_country_popularity_search desc nulls last, to_country_popularity_order desc nulls last, to_country_popularity_weight_kiwi desc nulls last limit ' . (int)$maxgeo . ' offset ' . (int)$geooffset),
					'to'=>\db::load('select distinct to_id,to_text,to_geo_type,max(id) as cache_id,
					                   max(to_popularity_order) as to_popularity_order,
									   max(to_popularity_search) as to_popularity_search,
									   max(to_popularity_weight_kiwi) as to_popularity_weight_kiwi
					                  from yandex.ya_cache_update' . $conditions . ' group by to_id,to_text,to_geo_type
					                  order by to_popularity_search desc nulls last, to_popularity_order desc nulls last, to_popularity_weight_kiwi desc nulls last limit ' . (int)$maxgeo . ' offset ' . (int)$geooffset),
					'route'=>\db::load('select from_id,from_text,from_geo_type,to_id,to_text,to_geo_type,max(id) as cache_id,
					                   max(route_popularity_order) as route_popularity_order,
									   max(route_popularity_search) as route_popularity_search,
									   max(route_popularity_weight_kiwi) as route_popularity_weight_kiwi
					                  from yandex.ya_cache_update' . $conditions . ' group by from_id,from_text,from_geo_type,to_id,to_text,to_geo_type
					                  order by route_popularity_search desc nulls last, route_popularity_order desc nulls last, route_popularity_weight_kiwi desc nulls last limit ' . (int)$maxgeo . ' offset ' . (int)$geooffset),
			];

		foreach ($geoCache as $k)
		{
			if (count($k))
			{
				return $this->generate($geoCache);
			}
		}
		$this->logmsg('Отсутствуют геокомбинации');
		
	}
	
	public function objArrNameSortDesc(&$arr)
	{
	    return usort($arr,function($a,$b){
    	    if (mb_strlen($a->name) == mb_strlen($b->name)) {
    	           return 0;
    	    }
    	    return (mb_strlen($a->name) > mb_strlen($b->name)) ? -1 : 1;
	    });
	}
	
	protected function logmsg($str)
	{
		
		if ($this->log)
		{

			var_dump($str);
			echo str_pad('',4096,"\0");
			ob_flush();
			flush();
		}
		else
		{
			echo str_pad("\r\n",4096,"\0");
			ob_flush();
			flush();
		}
	}
	
	public static function getRoutePrice($fromid,$toid,$autotypeid=null)
	{
	    static $routePrices;
	    $fromid=(int)$fromid;
	    $toid=(int)$toid;
	    if (isset($routePrices[$fromid][$toid][$autotypeid]))
	    {
	        return $routePrices[$fromid][$toid][$autotypeid];
	    }

	    $cacheprice=\db::get("select priceorigin, min(pricerub) as pricerub from cache_transfers_all where tfrom=$fromid and tto=$toid and date(utime) = date(now())" . ($autotypeid?' and atype='.(int)$autotypeid:'') . ' group by priceorigin order by pricerub limit 1');
	    if($cacheprice)
	    {
	        $routePrices[$fromid][$toid][$autotypeid]=(array)$cacheprice;
	        return (array)$cacheprice;
	    }
	    else
	    {
	        global $_transfers;
	        $_transfers = [];
	        $transfers = \Search::findTransfers([
	                'from' => $fromid,
	                'to' => $toid,
	                'currency' => 'RUB'
	        ]);
	        
	        if ($transfers['amount']==0)
	        {
	            return;
	        }
	                
	        foreach ($transfers['offers'] as $offer)
	        {
	            $routePrices[$fromid][$toid][$autotypeid]=[
	                        'priceorigin'=>$offer->priceOrig,
	                        'pricerub'=>$offer->priceRub               
	                ];
	        }
	        if (isset($routePrices[$fromid][$toid][$autotypeid]))
	        {
	            return $routePrices[$fromid][$toid][$autotypeid];
	        }
	    }
	}
	
	public static function mb_ucfirst($string) {
		$string = mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
        return $string;
	}
	
	public static function mb_lcfirst($string) {
	    $string = mb_strtolower(mb_substr($string, 0, 1)) . mb_substr($string, 1);
	    return $string;
	}
	
	public static function mb_trim( $string )
	{
		$string = preg_replace( "/(^\s+)|(\s+$)/us", "", $string );
		return $string;
	}
	
	public static function sanitize( $string )
	{
		return preg_replace('/[^a-zа-яё 0-9\(\)\:\.\!\-$€,]/iu', '', $string);
	}
	
	protected function generate($geoCache)
	{
		foreach ($this->phraseTemplates as $ptpl)
		{
		    $this->logmsg('Генерируем по фразе');
		    $this->logmsg($ptpl);
			if ((strpos($ptpl->name,'@src') !==false) && (strpos($ptpl->name,'@dst')===false))
			{
				foreach ($geoCache['from'] as $frm)
				{
				    $this->logmsg('генерируем по типу "откуда"');
				    $this->logmsg($frm);
				    if (!\places::id($frm->from_id))
				    {
				        continue;
				    }
					$this->mainGen($frm,'',$ptpl);
					if ($this->numAds>=$this->maxAds)
					{
						return $this->numAds;
					}
				}
			}
			elseif((strpos($ptpl->name,'@src') == false) && (strpos($ptpl->name,'@dst')!==false))
			{
				foreach ($geoCache['to'] as $to)
				{
				    $this->logmsg('генерируем по типу "куда"');
				    $this->logmsg($to);
				    if (!\places::id($to->to_id))
				    {
				        continue;
				    }
					$this->mainGen('',$to,$ptpl);
					if ($this->numAds>=$this->maxAds)
					{
						return $this->numAds;
					}
				}
			}
			elseif((strpos($ptpl->name,'@src')!==false) && (strpos($ptpl->name,'@dst')!==false))
			{
				foreach ($geoCache['route'] as $r)
				{
				    $this->logmsg('генерируем по типу "маршрут"');
				    $this->logmsg($r);
				    if (!\places::id($frm->from_id))
				    {
				        continue;
				    }
				    if (!\places::id($to->to_id))
				    {
				        continue;
				    }
					$this->mainGen($r,$r,$ptpl);
					if ($this->numAds>=$this->maxAds)
					{
						return $this->numAds;
					}
				}
			}
		}
	}
	
	protected function mainGen($frm='',$to='',$ptpl)
	{	
	
	    $fromid=@(int)$frm->from_id;
	    $toid=@(int)$to->to_id;
	    $adKey=$fromid . ':' . $toid . ':' . $ptpl->id;
	    if (isset($this->adKeys[$adKey]))
	    {
	        if (!isset($this->bids[$fromid . ':' .$toid]))
	        {   	        
    	        if ($bestroute=self::getBestRoute($frm, $to))
    	        {
    	            $this->saveBid($fromid,$toid,$bestroute->price_origin);
    	        }
	        }
	        $this->logmsg('Такое объявление уже существует');
	        return;
	    }
	    
		foreach ($this->mainTextTemplates as $mtpl)
		{
			if (strpos($ptpl->name,'такси')!==false)
			{
				$mtplstr=str_replace('@type', 'такси', $mtpl->name);
			}
			elseif (strpos($ptpl->name,'трансфер')!==false)
			{
				$mtplstr=str_replace('@type', 'трансфер', $mtpl->name);
			}
			else
			{
				$mtplstr=str_replace('@type', self::$types[$i%2], $mtpl->name);
			}
			$i++;
			
			$ad=$this->genAdText($ptpl->name,$mtplstr,@$frm->from_text,@$to->to_text);

			if (!$ad)
			{
				continue;
			}
			if (!isset($this->linksCache[$fromid][$toid]))
			{
				$adlinks=$this->genAdLinks($frm,$to);
				if (!$adlinks)
				{
					$this->linksCache[$fromid][$toid]=null;
					continue;
				}
	
				if ($frm && $to)
				{
					$mainUrl=self::LINKBASE . \Route::getUrl($frm->from_id,$to->to_id);
				}
				elseif($frm && !$to)
				{
					$mainUrl=self::LINKBASE . \Route::getHumanFriendlyUrl($frm->from_id);
				}
				elseif($to)
				{
					$mainUrl=self::LINKBASE . \Route::getHumanFriendlyUrl(null,$to->to_id);
				}
				
				if (!$mainUrl)
				{
				    $this->logmsg('Не удалось сгенерировать основную ссылку');
					return;
				}
				$this->linksCache[$fromid][$toid]=[
						'adlinks'=>$adlinks,
						'mainUrl'=>$mainUrl
				];
			}
			elseif(!empty($this->linksCache[$fromid][$toid]))
			{
			    $this->logmsg('Используем кеш ссылок');
			    $adlinks=$this->linksCache[$fromid][$toid]['adlinks'];
			    $mainUrl=$this->linksCache[$fromid][$toid]['mainUrl'];
			}
			else 
			{
			    $this->logmsg('Ранее уже не удалось сгенерировать нбс для такой геокомбинации');
			    return;
			}
			
			if ($frm)
			{
				$cacheId=$frm->cache_id;
			}
			elseif($to)
			{
				$cacheId=$to->cache_id;
			}
			if (!$cacheId)
			{
				$this->logmsg('отсутствует необходимый параметр cacheId');
				$this->logmsg('$frm:');
				$this->logmsg($frm);
				$this->logmsg('$to:');
				$this->logmsg($to);
				continue;			
			}
			$keyword=self::prepareMinusWord($ad['origTitle'],$fromid,$toid,$ptpl);
			if (isset($this->keywords[$keyword]))
			{
			    $this->logmsg('такое ключевое слово уже используется в другой группе объявлений, пропускаем');
			    $this->logmsg($keyword);
			    continue;			    
			}
			
			$paramsGroup=[
					'ptplId'=>$ptpl->id,
					'cacheId'=>$cacheId,
					'from_id'=>($frm?$frm->from_id:0),
					'to_id'=>($to?$to->to_id:0),
			        'keywords'=>$keyword
			];
			
			$paramsQlinkSet=[
					'link1_title'=>@$adlinks[0]['title'],
					'link1_href'=>@$adlinks[0]['url'],
					'link2_title'=>@$adlinks[1]['title'],
					'link2_href'=>@$adlinks[1]['url'],
					'link3_title'=>@$adlinks[2]['title'],
					'link3_href'=>@$adlinks[2]['url'],
					'link4_title'=>@$adlinks[3]['title'],
					'link4_href'=>@$adlinks[3]['url']
			];
			
			foreach ($paramsQlinkSet as $k=>$f)
			{
				$paramsQlinkSet[$k]=self::mb_trim($f);
				if (empty($f))
				{
					$paramsQlinkSet[$k]=null;
				}
			}
				
			if (!$this->dryrun)
			{
				$adgrp=\db::get("insert into yandex.ya_ad_group (group_template_id,cache_id,from_id,to_id,keywords) values ([ptplId],[cacheId],[from_id],[to_id],[keywords]); select lastval() as insid",$paramsGroup);
			}
			
			$qlinksetkey=sha1(implode(' ',array_values($paramsQlinkSet)));

			if (!isset($this->qlinksets[$qlinksetkey]))
			{
				if (!$this->dryrun)
				{
					$linkset=\db::get("insert into yandex.ya_sitelink (link1_title,link1_href,link2_title,link2_href,link3_title,link3_href,link4_title,link4_href) values ([link1_title],[link1_href],[link2_title],[link2_href],[link3_title],[link3_href],[link4_title],[link4_href]); select lastval() as insid",$paramsQlinkSet);
				}
				$this->qlinksets[$qlinksetkey]=$linkset->insid;
				$this->logmsg('сохранен набор БС, хеш ' . $qlinksetkey);
				$this->logmsg($paramsQlinkSet);
				$this->logmsg($this->qlinksets[$qlinksetkey]);				
			}
			else
			{
				$this->logmsg('использован сохраненный ранее набор БС, хеш ' . $qlinksetkey);
				$this->logmsg($paramsQlinkSet);
				$this->logmsg($this->qlinksets[$qlinksetkey]);
			}
					
			$this->logmsg('сохранена группа');
			$this->logmsg($paramsGroup);
			$this->logmsg($adgrp);
			$this->keywords[$keyword]++;
			
			
			if (!isset($this->bids[$fromid . ':' .$toid]))
			{
			    if ($bestroute=self::getBestRoute($frm, $to))
				{
                    $this->saveBid($fromid,$toid,$bestroute->price_origin);
				}
			}
			else
			{
			    $this->logmsg('использована сохраненная ранее ставка');
			    $this->logmsg($this->bids[$fromid . ':' .$toid]);
			}
			
			$paramsAd=[
					'title'=>self::mb_trim($ad['title']),
					'text'=>self::mb_trim($ad['text']),
					'mtplId'=>$mtpl->id,
					'ptplId'=>$ptpl->id,
					'url'=>self::mb_trim($mainUrl),
					'for_key'=>$ad['origTitle'],
					'linkset_id'=>$this->qlinksets[$qlinksetkey]
			];
			
			if (!$this->dryrun)
			{
				$ad=\db::get("insert into yandex.ya_ad (group_id, adtitle, adtext, ad_maintext_template_id, ad_phrase_template_id, url, for_key, linkset_id) 
							 values ({$adgrp->insid},[title],[text],[mtplId],[ptplId],[url],[for_key],[linkset_id]); select lastval() as insid",$paramsAd);
			}
	
			$this->numAds++;
			$this->logmsg('сохранено объявление ' . $this->numAds);
			$this->logmsg($paramsAd);
			$this->logmsg($ad);
			
			$this->adKeys[$adKey]++;

			break;
		}
	}
	
	protected static function getBestRoute($frm,$to)
	{
	    $fromid=@(int)$frm->from_id;
	    $toid=@(int)$to->to_id;
	    if (isset(self::$bestroutes[$fromid][$toid]))
	    {
	        return self::$bestroutes[$fromid][$toid];
	    }
	    else
	    {
    	    if (!empty($frm) && empty($to))
    	    {
    	        $prefer= 'to_geo_type' . (($frm->from_geo_type != 2205)?'<>':'=') . '2205 as prefer';
    	        $noaptoap= $frm->from_geo_type == 2205 ? ' and to_geo_type <> 2205':'';
    	        $r=\db::get("SELECT distinct to_id as to, price_origin, price_rub,
    	                max(route_popularity_search) as popularity_search,
    	                max(route_popularity_order) as popularity_order,
    	                max(route_popularity_weight_kiwi) as popularity_weight_kiwi,
    	                $prefer
    	                FROM yandex.ya_cache_update WHERE from_id=$fromid" . $noaptoap . " group by to_id, to_geo_type, price_origin, price_rub
    	                order by prefer, popularity_search desc nulls last, popularity_order desc nulls last, popularity_weight_kiwi desc nulls last, price_rub limit 1");
    	    }
            elseif(empty($frm) && !empty($to))
    		{
    		    $prefer = 'from_geo_type' . (($to->to_geo_type != 2205)?'<>':'=') . '2205 as prefer';
    		    $noaptoap = $to->to_geo_type == 2205 ? ' and from_geo_type <> 2205':'';
    			$r=\db::get("SELECT distinct from_id as from, price_origin, price_rub,
    			        max(route_popularity_search) as popularity_search,
    			        max(route_popularity_order) as popularity_order,
    			        max(route_popularity_weight_kiwi) as popularity_weight_kiwi,
    			        $prefer
    			        FROM yandex.ya_cache_update WHERE to_id=$toid" . $noaptoap . " group by from_id, from_geo_type, price_origin, price_rub
    			        order by prefer, popularity_search desc nulls last, popularity_order desc nulls last, popularity_weight_kiwi desc nulls last, price_rub limit 1");
            }
            elseif(!empty($frm) && !empty($to))
            {
                $r=\db::get("select * from yandex.ya_cache_update where from_id=$fromid and to_id=$toid");
            }
            self::$bestroutes[$fromid][$toid]=$r;
            return $r;
	    }
	}
	
	protected function saveBid($fromid,$toid,$priceorigin)
	{
	    list($prcval,$prccurr)=explode(' ',$priceorigin);
	    $prcrub=\Currencies::id($prccurr)->convert($prcval);
	    $bidparams=[
	            'from_id'=>$fromid,
	            'to_id'=>$toid,
	            'order_price'=>$prcval,
	            'order_currency'=>$prccurr,
	            'bid_value'=>min($this->maxbid,max($this->minbid,$prcrub/$this->ordertobidratio))
	    ];
	    if (!$this->dryrun)
	    {
	        \db::query('INSERT INTO yandex.ya_bid (from_id, to_id, order_price, order_currency, bid_value)
                                                                VALUES ([from_id], [to_id], [order_price], [order_currency], [bid_value]);',$bidparams);
	    }
	    $this->logmsg('сохранена ставка');
	    $this->logmsg($bidparams);
	    
	    $this->bids[$fromid . ':' .$toid]=(object)$bidparams;
	}
	
	protected function genAdText($titleTpl,$mainTextTpl,$src='',$dst='')
	{	
		if (!empty($src) && empty($dst))
		{
			$title=str_replace('@src', $src, $titleTpl);
			$adText=str_replace('@geo', $src, $mainTextTpl);
		}
		elseif (empty($src) && !empty($dst))
		{
			$title=str_replace('@dst', $dst, $titleTpl);
			$adText=str_replace('@geo', $dst, $mainTextTpl);
		}
		elseif (!empty($src) && !empty($dst))
		{
			$title=str_replace(['@src','@dst'], [$src,$dst], $titleTpl);
			$adText=str_replace('@geo', 'из ' . $src . ' в ' . $dst, $mainTextTpl);
		}
		$title=$title=self::mb_ucfirst($title);
		$title=self::sanitize($title);
		$title=self::mb_trim($title);
		
		$adText=self::sanitize($adText);
		$adText=self::mb_trim($adText);
		
		$origTitle=$title;
		$titleArr=explode(' ',$title);
		
		if (mb_strlen($title)>self::MAX_AD_EXTENDED_TITLE_LENGTH)
		{		
		  $title=preg_replace(self::$abbrev_repl_from,self::$abbrev_repl_to,$title);
		}
		
		if (mb_strlen($title)>self::MAX_AD_EXTENDED_TITLE_LENGTH)
		{
			$this->logmsg('Ошибка при генерации заголовка объявления');
			$this->logmsg('Заголовок превышает лимит расширенного заголовка' . self::MAX_AD_EXTENDED_TITLE_LENGTH . ' символов');
			$this->logmsg('Шаблон заголовка : ' . $titleTpl);
			$this->logmsg('Шаблон тела : ' . $mainTextTpl);
			if (!empty($src))
			{
				$this->logmsg('Исходная точка : ' . $src);
					
			}
			if (!empty($dst))
			{
				$this->logmsg('Точка назначения : ' . $dst);
			}
			$this->logmsg('Сгенерированный заголовок : ' . $title);			
			return;
		}
		
		$tomain=[];
		/*
		while(mb_strlen($title)>self::MAX_AD_EXTENDED_TITLE_LENGTH ) //Расширенная длинна заголовка до 52 символов, остальное удаляем...
		{
			array_pop($titleArr);
			$title=implode(' ',$titleArr);
		}
		*/
		
		if (count($titleArr)>self::MAX_KEYWORD_WORDS_COUNT)
		{
			$this->logmsg('Ошибка при генерации заголовка объявления');
			$this->logmsg('Заголовок превышает лимит ' . self::MAX_KEYWORD_WORDS_COUNT . ' слов, невозможно использовать в фразе');
			$this->logmsg('Шаблон заголовка : ' . $titleTpl);
			$this->logmsg('Шаблон тела : ' . $mainTextTpl);
			if (!empty($src))
			{
				$this->logmsg('Исходная точка : ' . $src);
					
			}
			if (!empty($dst))
			{
				$this->logmsg('Точка назначения : ' . $dst);
			}
			$this->logmsg('Сгенерированный заголовок : ' . $title);			
			return;
		}
		
		while(mb_strlen($title)>self::MAX_AD_BASIC_TITLE_LENGTH ) //Базовая длинна заголовка до 33 символов, остальное сносим в основной текст для получения расширенного заголовка
		{
			array_unshift($tomain,array_pop($titleArr));
			$title=implode(' ',$titleArr);
		}
		
		foreach($titleArr as $w)
		{
			if (mb_strlen($w)>self::MAX_AD_TITLE_WORD_LENGTH)
			{
				$this->logmsg('Ошибка при генерации объявления');
				$this->logmsg('Длинна слова "' . $w . '" в заголовке превышает лимит ' . self::MAX_AD_TITLE_WORD_LENGTH . ' символа.');
				$this->logmsg('Шаблон заголовка : ' . $titleTpl);
				$this->logmsg('Шаблон тела : ' . $mainTextTpl);
				if (!empty($src))
				{
					$this->logmsg('Исходная точка : ' . $src);
				
				}
				if (!empty($dst))
				{
					$this->logmsg('Точка назначения : ' . $dst);
				}			
				$this->logmsg('Сгенерированный заголовок : ' . $title);
				return;
			}
		}
		
		if (count($tomain))
		{
			$tomain=implode(' ',$tomain);
			$tomain=self::mb_ucfirst($tomain) . '!'; //, если в сумме заголовок + 1-е предложение <53 символов - расширенный заголовок
			$adText=$tomain . ' ' . $adText;
		}
		
		if (mb_strlen($adText)>self::MAX_AD_BODY_LENGTH)
		{
		  $adText=preg_replace(self::$abbrev_repl_from,self::$abbrev_repl_to,$adText);
		}
		
		if (mb_strlen($adText)>self::MAX_AD_BODY_LENGTH)
		{
			$this->logmsg('Ошибка при генерации объявления');
			$this->logmsg('Длинна тела объявления превышает ' . self::MAX_AD_BODY_LENGTH . ' символов');
			$this->logmsg('Шаблон заголовка : ' . $titleTpl);
			$this->logmsg('Шаблон тела : ' . $mainTextTpl);
			if (!empty($src))
			{
				$this->logmsg('Исходная точка : ' . $src);
					
			}
			if (!empty($dst))
			{
				$this->logmsg('Точка назначения : ' . $dst);
			}
			$this->logmsg('Сгенерированный заголовок : ' . $title);		
			$this->logmsg('Сгенерированное тело объявления : ' .$adText);
			return;
		}
		
		$mainArr=explode(' ',$adText);
		
		foreach($mainArr as $w)
		{
			if (mb_strlen($w)>self::MAX_AD_BODY_WORD_LENGTH)
			{
				$this->logmsg('Ошибка при генерации шаблона объявления');
				$this->logmsg('Длинна слова "' . $w . '" в теле объявления превышает лимит ' . self::MAX_AD_BODY_WORD_LENGTH . ' символа.');
				$this->logmsg('Шаблон заголовка : ' . $titleTpl);
				$this->logmsg('Шаблон тела : ' . $mainTextTpl);
				if (!empty($src))
				{
					$this->logmsg('Исходная точка : ' . $src);
						
				}
				if (!empty($dst))
				{
					$this->logmsg('Точка назначения : ' . $dst);
				}
				$this->logmsg('Сгенерированный заголовок : ' . $title);
				$this->logmsg('Сгенерированное тело объявления : ' .$adText);
				return;
			}
		}
	
		return[
				'title'=>$title,
				'origTitle'=>$origTitle,
				'text'=>$adText
		];
	}
	
	protected function fitLink($linktitle,$price)
	{
	    if (mb_strlen($linktitle . $price)>self::MAX_SITELINK_LENGTH)
	    {
	        $linktitle=preg_replace(self::$abbrev_repl_from, self::$abbrev_repl_to, $linktitle);
	    }
		if (mb_strlen($linktitle . $price)<=self::MAX_SITELINK_LENGTH)
		{
			return $linktitle . $price;
		}
		elseif (mb_strlen($linktitle)<=self::MAX_SITELINK_LENGTH)
		{
			return $linktitle;
		}
	}
	
	public static function prcToStr($prc)
	{
	    /*
		list($prcvalue,$prccurrency)=explode(' ',$prc['priceorigin']);
		if ($prccurrency!='RUB')
		{
		    $prc=round(\Currencies::id($prccurrency)->convert($prcvalue));
		}
		else
		{
		    $prc=round($prcvalue);
		}
		/
		return ' ' . $prc . self::$currencysymbols[$prccurrency];
		*/
	    if ($prc['pricerub']<=self::MAX_SITELINK_PRICE_RUB)
	    {
	       return ' ' . round($prc['pricerub']) . self::$currencysymbols['RUB'];
	    }
	}
	
	protected static function tryRouteQLink($fromid,$toid,$linktitle,$price,&$links,&$totalLink,$autoType=null)
	{
		$linktitle=self::mb_trim($linktitle);
		$linktitle=self::sanitize($linktitle);
		$priceLinkStr = self::prcToStr($price);
		$fitLink=self::fitLink($linktitle,$priceLinkStr);
		
		foreach($links as $link)
		{
		    if ($link['title']==$fitLink)
		    {
		        return;
		    }
		}
		
		if ($fitLink && (mb_strlen($totalLink . $fitLink) < self::MAX_SITELINKS_TOTAL_LENGTH))
		{
			$totalLink .= $fitLink;
			$url = \Route::getHumanFriendlyUrl($fromid,$toid);
			if (!$url)
			{
				return;
			}
			if (is_object($autoType))
			{
			    $url .= '/' . $autoType->code;
			}
			$links[]=[
					'title'=>$fitLink,
					'url'=>self::LINKBASE . $url,
			        'priceorigin'=>$price['priceorigin']
			];
		}
	}
	
	public function genAdLinks($frm='',$to='')
	{
		$totalLink='';
		
		$fromid=@(int)$frm->from_id;
		$toid=@(int)$to->to_id;

		if (!empty($frm) && empty($to))
		{
		    $prefer= 'to_geo_type' . (($frm->from_geo_type != 2205)?'<>':'=') . '2205 as prefer';
		    $noaptoap= $frm->from_geo_type == 2205 ? ' and to_geo_type <> 2205':'';
			$popularRoutesFrom=\db::load("SELECT distinct to_id as to, price_origin, price_rub,
			                                     max(route_popularity_search) as popularity_search, 
			                                     max(route_popularity_order) as popularity_order,
			                                     max(route_popularity_weight_kiwi) as popularity_weight_kiwi,
			                                     $prefer 
			                                     FROM yandex.ya_cache_update WHERE from_id={$frm->from_id}" . $noaptoap . " group by to_id, to_geo_type, price_origin, price_rub
			                                     order by prefer, popularity_search desc nulls last, popularity_order desc nulls last, popularity_weight_kiwi desc nulls last, price_rub");
			                                     
			if(count($popularRoutesFrom))
			{
			    if (!isset(self::$bestroutes[$fromid][$toid]))
			    {
			        self::$bestroutes[$fromid][$toid]=$popularRoutesFrom[0];
			    }
			    
				foreach($popularRoutesFrom as $prf)
				{
				    if (!($pto=\places::id($prf->to)))
				    {
				        continue;
				    }
					$linktitle=\wordCase::from('allativus', $pto->name);
					$price=self::getRoutePrice($frm->from_id, $prf->to);
					if (!$price)
					{
					    continue;
					}
					self::tryRouteQLink($frm->from_id,$prf->to,$linktitle,$price,$links,$totalLink);

					if (mb_strlen($totalLink)>=self::MAX_SITELINKS_TOTAL_LENGTH)
					{
						return $links;
					}					
					if (count($links)>1)
					{
						break;
					}
				}
			}
		}
		elseif(empty($frm) && !empty($to))
		{
		    $prefer= 'from_geo_type' . (($to->to_geo_type != 2205)?'<>':'=') . '2205 as prefer';
		    $noaptoap=$to->to_geo_type == 2205 ? ' and from_geo_type <> 2205':'';
			$popularRoutesTo=\db::load("SELECT distinct from_id as from, price_origin, price_rub,
			        max(route_popularity_search) as popularity_search,
			        max(route_popularity_order) as popularity_order,
			        max(route_popularity_weight_kiwi) as popularity_weight_kiwi,
			        $prefer
			        FROM yandex.ya_cache_update WHERE to_id={$to->to_id}" . $noaptoap . " group by from_id, from_geo_type, price_origin, price_rub
			        order by prefer, popularity_search desc nulls last, popularity_order desc nulls last, popularity_weight_kiwi desc nulls last, price_rub");
			        		
			if(count($popularRoutesTo))
			{
			    if (!isset(self::$bestroutes[$fromid][$toid]))
			    {
			        self::$bestroutes[$fromid][$toid]=$popularRoutesTo[0];
			    }
				foreach($popularRoutesTo as $prt)
				{
				    if (!($pfrom=\places::id($prt->from)))
				    {
				        continue;
				    }				    
					$linktitle=\wordCase::from('casus', $pfrom->name);
					$price=self::getRoutePrice($prt->from, $to->to_id);
					if (!$price)
					{
					    continue;
					}
					self::tryRouteQLink($prt->from,$to->to_id,$linktitle,$price,$links,$totalLink);
						
					if (mb_strlen($totalLink)>=self::MAX_SITELINKS_TOTAL_LENGTH)
					{
						return $links;
					}					
					if (count($links)>1)
					{
						break;
					}
				}
			}
		}
		elseif (!empty($frm) && !empty($to))
		{		    
		    $atypes=\db::load("select ct.atype,cta.pricerub,cta.priceorigin as price_origin from cache_transfers ct join cache_transfers_all cta on cta.tfrom=ct.tfrom and cta.tto=ct.tto and cta.atype=ct.atype and cta.tkey=ct.tkey where ct.tfrom={$frm->from_id} and ct.tto={$to->to_id} order by ct.cost");
			if (count($atypes))
			{
			    if (!isset(self::$bestroutes[$fromid][$toid]))
			    {
			        self::$bestroutes[$fromid][$toid]=$atypes[0];
			    }
			    foreach($atypes as $at)
			    {
			        $autoType=\lib::id('autoTypes',$at->atype);
			        if ($autoType)
			        {
			            $price=self::getRoutePrice($frm->from_id,$to->to_id,$autoType->id);
			            if (!$price)
			            {
			                continue;
			            }
			            self::tryRouteQLink($frm->from_id,$to->to_id,$autoType->name,$price,$links,$totalLink,$autoType);
			        }
			        if (mb_strlen($totalLink)>=self::MAX_SITELINKS_TOTAL_LENGTH)
			        {
			            return $links;
			        }
			        if (count($links)>3)
			        {
		                 return $links;
			        }
			    }			    
			}
		}

		$link3Title='Отзывы';
		if (mb_strlen($totalLink . $link3Title)>=self::MAX_SITELINKS_TOTAL_LENGTH)
		{
			return $links;
		}
		
		$links[]=[
				'title'=>$link3Title,
				'url'=>self::LINKBASE . '/feedback'			
		];
		
		if (count($links)>3)
		{
		    return $links;
		}
		
		$totalLink .= $link3Title;
		
		$link4Title='Встретим с табличкой';
		if (mb_strlen($totalLink . $link4Title)>=self::MAX_SITELINKS_TOTAL_LENGTH)
		{
			return $links;
		}
		
		$links[]=[
				'title'=>$link4Title,
				'url'=>self::LINKBASE . '/about'
		];
		
		$totalLink .= $link4Title;
		return $links;
	}
	
	public function prepareMinusWord($string,$fromid,$toid,$ptpl) {
	    
	    $minusstr=$ptpl->dtext1;
	    $plusstr=$ptpl->dtext2;
	    
	    if (!empty($plusstr))
	    {
	        $plus=explode(' ',$plusstr);
	        $replfrompat=[];
	        $replto=[];
	        foreach ($plus as $p)
	        {
	            $replfrompat[]= '/\b' . preg_quote($p) . '\b/iu';
	            $replto[]='+' . $p;
	        }
	    }

	    
	    if (!empty($replfrompat))
	    {
	       $inputString = preg_replace($replfrompat, $replto, $string);
	    }
	    else
	    {
	        $inputString=$string;
	    }
	     
	    if (!empty($minusstr))
	    {
	        if ((mb_stristr($minusstr, '-индивидуальный') !== false) && !isset($this->adKeys[$fromid . ':' . $toid . ':61918' ]))
	        {
	            $minusstr = preg_replace('/\s?+\-индивидуальный/iu', '', $minusstr);
	        }
	        $inputString .= ' ' . $minusstr;
	    }
	    
	    return $inputString;
	}
}
