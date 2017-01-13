<?php
/**
 * Задача обновления курсорв валют по расписанию. Сегодняшний и на завтра.
 * На выходных курс ЦБ не обновляется.
 */
namespace console\components\tasks;

use common\models\lib\Currency;
use common\models\lib\CurrencyRate;
use console\components\tasks\ShedulerTaskAbstract;
use yii\base\ErrorException;
use yii\helpers\ArrayHelper;

class RatesUpdaterTask extends ShedulerTaskAbstract
{
    const CBRATESURL = 'http://www.cbr.ru/scripts/XML_daily.asp?date_req=';

    public function run($params = [])
    {
        $this->addLog('Run RatesUpdaterTask Task ' . date('r'));

        try {
            
            $ourCurrencies = ArrayHelper::index(Currency::getArrayCodeList(), 'code');                                    
            
            $requestUrl = self::CBRATESURL . date('d/m/Y');
            $this->addLog("request Url: {$requestUrl}");
            $todayRates = simplexml_load_file($requestUrl);
            $this->addLog("received " . count($todayRates) . ' items');            
            if ((string)$todayRates['name'] == 'Foreign Currency Market') {
                $rateDate = (string)$todayRates['Date'];                
                self::updateRates($rateDate, $todayRates->Valute, $ourCurrencies);
            }

            $requestUrl = self::CBRATESURL . date('d/m/Y', strtotime('+5 day'));
            $this->addLog("request Url: {$requestUrl}");
            $tomorrowRates = simplexml_load_file($requestUrl);
            $this->addLog("received " . count($tomorrowRates) . ' items');
            if ((string)$tomorrowRates['name'] == 'Foreign Currency Market') {
                $rateDate = (string)$tomorrowRates['Date'];
                self::updateRates($rateDate, $tomorrowRates->Valute, $ourCurrencies);
            }   

        } catch (ErrorException $e) {
            $this->addLog('Error: ' . $e->getMessage() . ' ' . date('r'), true);
            $this->markEndOfTask();
        } catch (PDOException $e) {
            $this->addLog('Error: ' . $e->getMessage() . ' ' . date('r'), true);
            $this->markEndOfTask();
        }
    }

    /**
     * @param $rateDate дата на которую курс вернул ЦБ
     * @param array $rates - массив курсов валют ЦБ
     * @param $ourCurrencies - массив индексированный по 3-значному коду по колонкам id,code из нашего справочника Currencies
     * @throws \Exception
     */
    public static function updateRates($rateDate, &$rates, &$ourCurrencies) {
        foreach ($rates as $cbRate) {
            $cc=(string)$cbRate->CharCode;            
            if (isset($ourCurrencies[$cc])) {
                $cbConversionRate=(float)$cbRate->Value/(float)$cbRate->Nominal;
                $existingRate=CurrencyRate::findOne(['date_rate'=>$rateDate,
                     'currency_id_from' => $ourCurrencies[$cc]['id'],
                     'currency_id_to' => $ourCurrencies['RUB']['id']]);
                if ($existingRate) {
                    $existingRate->rate = $cbConversionRate;
                    $existingRate->update();
                }
                else
                {
                    $newRate=new CurrencyRate();
                    $newRate->date_rate=$rateDate;
                    $newRate->currency_id_from=$ourCurrencies[$cc]['id'];
                    $newRate->currency_id_to=$ourCurrencies['RUB']['id'];
                    $newRate->rate=$cbConversionRate;
                    $newRate->save();
                }
            }
        }
    }
}