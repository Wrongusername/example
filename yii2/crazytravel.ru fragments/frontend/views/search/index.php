<?php
use \Yii;
use \yii\helpers\Html;
use \frontend\widgets\MainLeftMenu;
use \frontend\models\InsuranceSearchForm;
use \yii\widgets\ActiveForm;
use \yii\helpers\ArrayHelper;
use \yii\helpers\BaseHtml;
use \yii\bootstrap\Progress;
use frontend\widgets\CallMe;

/* @var $this yii\web\View */

//настройки заголовка
$this->title = "Поиск";
$this->params['breadcrumbs'][] = $this->title;

//получим версию файлов
$fVersion = isset(Yii::$app->params['fileVersion']) ? Yii::$app->params['fileVersion'] : "";

$nationality = [ '0' => 'Другое', '1' => 'Россия'];

//получение Доп. параметров поиска
$insuranceFormModel = new InsuranceSearchForm();

//по приоритету загружаем данные из Пост запроса (т.к. это будет означать, что данные были переданы при нажатии на кнопку "пересчитать"
if(isset(Yii::$app->request->post()['InsuranceSearchForm'])) {
    $pcount=Yii::$app->request->post()['InsuranceSearchForm']['peopleCount'];
    $pcount=$pcount?$pcount:1;
    $insuranceFormModel->updateRules($pcount);
    $insuranceFormModel->load(Yii::$app->request->post());
} else {
    $pcount=Yii::$app->request->get()['InsuranceForm']['peopleCount']; 
    $pcount=$pcount?$pcount:1;
    $insuranceFormModel->updateRules($pcount);
    $insuranceFormModel->load(Yii::$app->request->get(), 'InsuranceForm'); //здесь данные будут получать из другой модели
    if ((!$insuranceFormModel->dateStart) || (!$insuranceFormModel->dateEnd))
    {
	$date = new \DateTime();
	$date->modify('+1 month');
	$date->setTime(12,0,0);
	
	$d = $date->format('d');
	$m = $date->format('m');
	$Y = $date->format('Y');
	
	$date->setDate($Y , $m , 3);
	$insuranceFormModel->dateStart=date('d.m.Y',$date->getTimestamp());
	$date->setDate($Y , $m , 10);
	$insuranceFormModel->dateEnd=date('d.m.Y',$date->getTimestamp());
    }
    if (!$insuranceFormModel->peopleCount)
	$insuranceFormModel->peopleCount=1;

	
    if (isset(Yii::$app->request->get()['InsuranceForm']) )
    {
	if (isset($date))
		$period=7;
	else
		$period=ceil((strtotime($insuranceFormModel->dateEnd) - strtotime($insuranceFormModel->dateStart))/60/60/24);

	//if ($period<=14)
        Yii::$app->view->registerJs("
		defaultInit=1;
		period=$period;", \yii\web\View::POS_HEAD, "0" );

    Yii::$app->view->registerJs("
	var initInterval = setInterval(function()
	{
		if (typeof $('#ins-form').data('yiiActiveForm') != 'undefined')
		{
			console.log('autosubmit');
			$('#sbmBtn').click();
			clearInterval(initInterval);
		}
	},100);	", \yii\web\View::POS_READY);

    }

    //предварительная проверка данных
    //если это первое попадание на страницу, то заполним данные по умолчанию
    for($ind = 0; $ind < InsuranceSearchForm::$maxPeopleSize; $ind++) {
        $birthStr = "birthday$ind";
        $nationalStr = "nationality$ind";
        if($insuranceFormModel->$birthStr == null) {
            $insuranceFormModel->$birthStr = "01.01.1980";
        }
        if($insuranceFormModel->$nationalStr == null) {
            $insuranceFormModel->$nationalStr = 1; //$nationality[1];
        }
    }
}

$insuranceFormModel->coverFilter5=$insuranceFormModel->coverFilter5?$insuranceFormModel->coverFilter5:0;
$insuranceFormModel->riskType=$insuranceFormModel->riskType?$insuranceFormModel->riskType:1;


//если кол-во людей введено некорректно по каким либо причинам. Устанавливаем количество в 4.
if($insuranceFormModel->peopleCount < 1 || $insuranceFormModel->peopleCount >  InsuranceSearchForm::$maxPeopleSize) {
    $insuranceFormModel->peopleCount = 4;
}

$this->registerJsFile("@web/js/vendor/numeral.min.js");
$this->registerJsFile("@web/js/vendor/numeral.languages.min.js");
$this->registerCssFile("@web/css/search-form.css?v=$fVersion");
$this->registerJsFile("@web/js/search-form.js?v=$fVersion");
//т.к. это "Фреймворк" по селектору)) то его необходимо Отображать выше всех

$this->registerJsFile("@web/js/vendor/select2.full.min.js", ['position' => \yii\web\View::POS_BEGIN], '0'); //const POS_READY = 4;
$this->registerCssFile("@web/css/select2.css");

?>
<div class="content-header">
    <div class="content-header-inner">
        <div class="content-header-l">
            <?php echo MainLeftMenu::widget(); ?>
            <div class="content-header-h1">Есть вопросы?</div>
            <div class="content-header-h2">Найди ответ</div>
            <div class="content-header-cont">
                <div class="icon icon1"></div>
                <div class="ttl">По телефону</div>
                <div class="content-header-expl">
                    Позвоните по номеру<br/>
                    8 (495) 777-23-77<br/>
                    или закажите обратный звонок
                    <button id="dcm2" class="content-header-btn show-call-me" type="button">Перезвоните мне</button>
                </div>
            </div>
            <div class="content-header-cont">
                <div class="icon icon2"></div>
                <div class="ttl">Онлайн-консультант</div>
                <div class="content-header-expl">
                    Мы ответим на все<br/>
                    ваши вопросы<br/><br/>
                    <button class="content-header-btn" type="button" id="custom-chatra-button">Перейти в онлайн-чат</button>
                </div>
            </div>
            <div class="content-header-cont page-side-note note1">
                  <p>Для оформления страховки<br>Вам нужно иметь под рукой<br>перечисленные документы.</p>
                  <p>Набор документов<br>отличается в зависимости<br>от компании:</p>
                  <ul>
                     <li>гражданский паспорт страхователя</li>
                     <li>заграничные паспорта всех застрахованных лиц</li>
                   </ul>
            </div>
        </div>
        <div class="content-header-r">
            <div class="search">
		<?php $searchAdvForm = ActiveForm::begin([
                            'id' => 'ins-form',
			    'method' => 'post',
                            'action' => ['offers'],
 			    'enableAjaxValidation' => true,
			    'enableClientValidation' => true,
			    'ajaxParam'=>'validate'
                        ]);
		?>
                <div class="title">
                    <h1 class="title-h1">Выберите вашу страховку</h1>
                </div>
                <div class="search-query">
		    <div class="search-query-wrapper">
			<div class="search-query-row clearfix">
				<div class="search-query-cell">
				<?php
                            	    echo $searchAdvForm->field($insuranceFormModel, 'country')->dropDownList(
                                    ArrayHelper::map($countryList, 'id', 'name_ru'), [
                                        'id' => 'country-sel'
                                    ]);		
				?>
				</div>
				<div class="search-query-cell dateperiod"><p>Период действия</p>
				<?php
                            	echo $searchAdvForm->field($insuranceFormModel, 'dateStart')
                            		->textInput([
                              	  'id' => "date_start",
                                	'class' => 'index-header-form-inp setDatepick',
                                	'placeholder' => 'Дата начала поездки',
                                	//'readonly' => true, ///чтобы никто вручную не вводил числа
                            		])->label(false);
				?>
				<span>-</span>
				<?php
                            	echo $searchAdvForm->field($insuranceFormModel, 'dateEnd')
                            	   ->textInput([
                            	   'id' => "date_end", 
                             	   'class' => 'index-header-form-inp setDatepick',
                             	   'placeholder' => 'Дата окончания поездки', 
                              	   //'readonly' => true, ///чтобы никто вручную не вводил числа
                            	   ])->label(false);
				?>
                <span id="spanDateErrStartEnd" class="myErrText myDisplayNone"></span>
				</div>
				<div class="search-query-cell">
				<?php
                            	echo $searchAdvForm->field($insuranceFormModel, 'riskType')->dropDownList(
                                    ArrayHelper::map($riskList, 'id', 'name'), [
                                        'id' => 'risk-sel'
                                    ])->label("Спорт");				
				?>
				</div>
			</div>
			<div class="search-query-row clearfix">
				<div class="search-query-cell">
				<?php
				echo $searchAdvForm->field($insuranceFormModel, 'peopleCount')->dropDownList(
                                    [
					'1'=>'1 турист',
					'2'=>'2 туриста',
					'3'=>'3 туриста',
					'4'=>'4 туриста'
				    ]
				)->label("Количество человек",['class' => 'searchLabel']);		
				?>
				</div>
				<div class="search-query-cell touristcb">
					<p><input class='detailcb' id='age_cb' type="checkbox"/>Есть младше 16 или старше 64</p>
					<p><input class='detailcb' id='citizen_cb' type="checkbox"/>Есть не-граждане РФ</p>
				</div>
				<div class="search-query-cell">
					<?php echo Progress::widget([
     						'percent' => 0,
     						'barOptions' => ['class' => 'progress-bar-success'],
     						'options' => ['class' => 'active progress-striped']
					]); ?>	
		    			<div class="search-filter-btn">
                         			<?php echo Html::submitButton('Рассчитать стоимость', ['id' => 'sbmBtn', 'class' => 'btn-mrgn-top search-filter-recount']); ?>
		    			</div>
				</div>
			</div>
			<?php
                                for($ind = 0; $ind < InsuranceSearchForm::$maxPeopleSize; $ind++) {
                                    $num = $ind + 1;

                                    echo '<div class="search-query-row clearfix tourist-details">';

                                    echo '<div class="search-query-cell">';
				    echo "<p class='touristlabel'>Турист $num</p>";
                                    echo "</div>";

                                    echo '<div class="search-query-cell">';                              
                                    echo $searchAdvForm->field($insuranceFormModel, "nationality{$ind}")->dropDownList(
                                        $nationality, [
                                            'id' => "insurancesearchform-nationality{$ind}",
                                            'class' => 'index-header-form-inp country-selector',
                                        ])->label("Гражданство", ['class' => '']);
                                    echo "</div>";

                                    echo '<div class="search-query-cell">';
                                    
                                    echo $searchAdvForm->field($insuranceFormModel, "birthday{$ind}")
                                        ->textInput([
                                            'id' => "birthdayId{$ind}",
                                            'class' => 'index-header-form-inp setDatepickBirth',
                                            //'readonly' => true, ///чтобы никто вручную не вводил числа
                                        ])->label("Дата рождения", ['class' => '']);
                                        echo "<span id='spanDateErr{$ind}' class='myErrText'></span>";
                                    echo '</div>';

                                    echo '</div>';
                                }

                    	 for($i = 1; $i<=5; $i++) {
				     echo $searchAdvForm->field($insuranceFormModel, "coverFilter$i")->hiddenInput()->label("", ['class' => 'myHidden']);
			 }

			 //echo $searchAdvForm->errorSummary($insuranceFormModel);

			 ActiveForm::end();						 
		?>
		</div></div>


                <div class="search-filter">
                    <div class="search-filter-inner">
                        <div class="search-filter-line">
                            <div class="search-filter-cell">
                                <div class="limits-lugg">
                                    <div class="limits-range-ruler">
                                        <span class="limits-range-grade limits-range-grade_0">0</span>
                                        <span class="limits-range-grade" style="left: 25%;">500</span>
                                        <span class="limits-range-grade" style="left: 50%;">1000</span>
                                        <span class="limits-range-grade" style="left: 75%;">1500</span>
                                        <span class="limits-range-grade limits-range-grade_100" style="right: 0;">2000</span>
                                    </div>
                                    <div class="limits-range-wrap">
                                        <div class="luggage-range"></div>
                                        <div class="limits-range-lbl">Багаж</div>
                                        <div class="limits-range-val off">Без страховки</div>
                                        <!--a href="#" class="limits-range-q"></a-->
                                    </div>
                                </div>
                            </div>
                            <div class="search-filter-cell">
                                <div class="limits-accident">
                                    <div class="limits-range-ruler">
                                        <span class="limits-range-grade limits-range-grade_0">0</span>
                                        <span class="limits-range-grade" style="left: 30%;">3000</span>
                                        <span class="limits-range-grade" style="left: 50%;">5000</span>
                                        <span class="limits-range-grade" style="left: 70%;">7000</span>
                                        <span class="limits-range-grade limits-range-grade_100" style="right: 0;">10000</span>
                                    </div>
                                    <div class="limits-range-wrap">
                                        <div class="accident-range"></div>
                                        <div class="limits-range-lbl">Несчастный случай</div>
                                        <div class="limits-range-val">Без страховки</div>
                                        <!--a href="#" class="limits-range-q"></a-->
                                    </div>
                                </div>
                            </div>
                            <div class="search-filter-cell">
                                <div class="limits-liability">
                                    <div class="limits-range-ruler">
                                        <span class="limits-range-grade limits-range-grade_0">0</span>
                                        <span class="limits-range-grade" style="left: 20%;">10000</span>
                                        <span class="limits-range-grade" style="left: 40%;">20000</span>
                                        <span class="limits-range-grade" style="left: 60%;">30000</span>
                                        <span class="limits-range-grade limits-range-grade_100" style="right: 0;">50000</span>
                                    </div>
                                    <div class="limits-range-wrap">
                                        <div class="liability-range"></div>
                                        <div class="limits-range-lbl">Гражд. ответств.</div>
                                        <div class="limits-range-val">Без страховки</div>
                                        <!--a href="#" class="limits-range-q"></a-->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="search-filter-line" style="display:none">
                            <div class="search-filter-cell">
                                <div class="limits-apartment">
                                    <div class="limits-range-ruler">
                                        <span class="limits-range-grade limits-range-grade_0">0</span>
                                        <span class="limits-range-grade limits-range-grade_100" style="right: 0;">10000</span>
                                    </div>
                                    <div class="limits-range-wrap">
                                        <div class="apartment-range"></div>
                                        <div class="limits-range-lbl">Квартира</div>
                                        <div class="limits-range-val">Без страховки</div>
                                        <!--a href="#" class="limits-range-q"></a-->
                                    </div>
                                </div>
                            </div>
                            <div class="search-filter-cell">	
                            </div>



                        </div>
                    </div>
                </div>
                <div class="title">
                    <h2 class="title-h1">Предложения страховых партнёров<span id='precalcmsg'><span></h2>
                </div>
                <div class="search-results">
                <table class="search-results-tbl">
                    <thead>
                        <tr>
                            <th>Компания</th>
                            <th><a id='ins-amount-sort-desc' class='sort-desc'>&#8595;</a>&nbsp;<a id='ins-amount-sort-asc' class='sort-desc'>&#8593;</a>&nbsp;Сумма</th>
                            <th class="search-results-feat"><a id='ins-luggage-sort-desc' class='sort-desc'>&#8595;</a>&nbsp;<a id='ins-luggage-sort-asc' class='sort-asc'>&#8593;</a>&nbsp;Багаж</th>
                            <th class="search-results-feat"><a id='ins-accident-sort-desc' class='sort-desc'>&#8595;</a>&nbsp;<a id='ins-accident-sort-asc' class='sort-asc'>&#8593;</a>&nbsp;Несч</th>
                            <th class="search-results-feat"><a id='ins-liablity-sort-desc' class='sort-desc'>&#8595;</a>&nbsp;<a id='ins-liability-sort-asc' class='sort-asc'>&#8593;</a>&nbsp;Гражд</th>
                            <th class="search-results-feat"><a id='ins-apartment-sort-desc' class='sort-desc'>&#8595;</a>&nbsp;<a id='ins-apartment-sort-asc' class='sort-asc'>&#8593;</a>&nbsp;Квартира</th>
                            <th class="search-results-feat">Отмена</th>
                            <th colspan="2"><a id='ins-price-sort-desc' class='sort-desc'>&#8595;</a>&nbsp;<a id='ins-price-sort-asc' class='sort-asc'>&#8593;</a>&nbsp; стоимость за <?php echo $insuranceFormModel->peopleCount . ' ' . ($insuranceFormModel->peopleCount>1?'полиса':'полис'); ?> </th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>

                </div>
            </div>
        </div>
    </div>
</div>
<div class="page-bottom-warning">
    В отличие от обычного (бумажного) полиса, выписываемого страховым агентом или в консульстве, электронный полис Вы получаете напрямую из ИТ-системы страховой компании. Поэтому вся информация о новом полисе моментально доступна в страховой компании, минуя отчеты агентов и ручной ввод в базу.
</div>
<?php
$this->registerJsFile("@web/js/vendor/wnumb.js");
$this->registerJsFile("@web/js/vendor/selectivizr-min.js");
$this->registerJsFile("@web/js/vendor/jquery.mCustomScrollbar.min.js");
$this->registerJsFile("@web/js/vendor/jquery.nouislider.all.min.js");
$this->registerJsFile("@web/js/plugins.js?v=$fVersion");
$this->registerJsFile("@web/js/main.js?v=$fVersion");

$this->registerJsFile("@web/js/default_search.js?v=$fVersion");
?>
<!--[if lt IE 9]><script src="js/vendor/html5shiv.js"</script><![endif]-->
