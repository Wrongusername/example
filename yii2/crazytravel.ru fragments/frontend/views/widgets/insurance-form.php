<?php
use \yii\helpers\Html;
use \yii\widgets\ActiveForm;
use \yii\helpers\ArrayHelper;
use \frontend\models\InsuranceForm;

    $fVersion = isset(Yii::$app->params['fileVersion']) ? Yii::$app->params['fileVersion'] : "";
    $this->registerCssFile('@web/css/insurance-form.css?v=' . $fVersion);
    $this->registerJsFile('@web/js/insurance-form.js?v=' . $fVersion);
    
    //т.к. это "Фреймворк" по селектору)) то его необходимо Отображать выше всех
    $this->registerJsFile("@web/js/vendor/select2.full.min.js", ['position' => \yii\web\View::POS_BEGIN], '0'); //const POS_READY = 4;
    $this->registerCssFile("@web/css/select2.css");
    
    $insuranceFormModel = new InsuranceForm();

/*
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
*/

?>
<div class="content-header-r">
    <div class="index-header-form">
        <!--<form >-->
        <?php $insForm = ActiveForm::begin(['id' => 'ins-form']); ?>
            <div class="index-header-form-mid">
                <div class="index-header-form-ttl">Рассчитайте стоимость вашей страховки</div>
                <div class="index-header-form-line">
                    <div class="index-header-form-cell">
                        <?php echo $insForm->field($insuranceFormModel, 'country')->dropDownList(
                                ArrayHelper::map($countryList, 'id', 'name_ru'), [
                                    //'id' => 'country-sel', чтобы отрабатывала проверка на ошибки (yii2) - заполнять нельзя
                                    'prompt' => 'Страна', 
                                    'class' => 'index-header-form-inp country-selector',
                                ])->label("");
                        ?>
                    </div>
                    <div class="index-header-form-cell">
                        <p class="index-header-form-p">
                            Если Вы планируете посетить несколько стран Шенгена,<br>
                            выберите страну въезда.
                        </p>
                    </div>
                </div>
                <div class="index-header-form-line">
                    <div class="index-header-form-cell">
                        <?php echo $insForm->field($insuranceFormModel, 'dateStart')
                            ->textInput([
                                'id' => "insuranceform-datestart",
                                'class' => 'index-header-form-inp setDatepick',
                                'placeholder' => 'Дата начала',
                                'readonly' => true, ///чтобы никто вручную не вводил числа
                            ])->label(""); ?>
                        <span id="dStartErrTxt" class="myErrText"></span>
                    </div>
                    <div class="index-header-form-cell">
                        <p class="index-header-form-p">
                            Будут застрахованы все дни<br>
                            между выбранными датами.
                        </p>
                    </div>
                </div>
                <div class="index-header-form-line">
                    <div class="index-header-form-cell">
                        <?php echo $insForm->field($insuranceFormModel, 'dateEnd')
                            ->textInput([
                                'id' => "insuranceform-dateend", 
                                'class' => 'index-header-form-inp setDatepick',
                                'placeholder' => 'Дата окончания', 
                                'readonly' => true, ///чтобы никто вручную не вводил числа
                            ])->label(""); ?>
                        <span id="dEndErrTxt" class="myErrText"></span>
                    </div>
                    <div class="index-header-form-cell">
                        <p class="index-header-form-p">
                            
                        </p>
                    </div>
                </div>
                <div class="index-header-form-line">
                    <div class="index-header-form-cell">
                        <?php echo $insForm->field($insuranceFormModel, 'peopleCount')->dropDownList(
                                $peopleCount, [
                                    'prompt' => 'Количество туристов',
                                    'class' => 'index-header-form-inp country-selector numpeople_selector',
                                ]); ?>
                        
                    </div>
                    <div class="index-header-form-cell">
                        <p class="index-header-form-p">
                            В один полис может быть вписано<br>
                            до 4-х туристов.
                        </p>
                    </div>
                </div>
                <div class="index-header-form-bot">
                    <div class="index-header-form-line">
                        <div class="index-header-form-notice">
                            Вы можете изучить <a href="/main/insurance-regulations">подробные правила страхования</a><br>для каждого из наших партнёров
                        </div>
                        <div class="index-header-form-btn">
                            <?= Html::submitButton('Рассчитать стоимость', [
                                'id' => 'sbmBtn',
                                'class' => 'index-header-form-submit']) ?>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php ActiveForm::end(); ?>
        <!--</form>-->
    </div>
</div>