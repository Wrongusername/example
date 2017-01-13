<?php

namespace frontend\controllers;

use \Yii;
use \frontend\models\InsuranceForm;

class MainController extends \yii\web\Controller {
    
    //действие для отображения главной страницы сайта
    public function actionIndex() {
        
        $insFormModel = new InsuranceForm();
      
        //если этот набор параметров был передан с этой формы, то перейдем на форму поиска
        if($insFormModel->load(Yii::$app->request->post()) && $insFormModel->validate()) {
            $this->redirect([
                '/search/index',
                'InsuranceForm[country]' => $insFormModel->country,
                'InsuranceForm[dateStart]' => $insFormModel->dateStart,
                'InsuranceForm[dateEnd]' => $insFormModel->dateEnd,
                'InsuranceForm[peopleCount]' => $insFormModel->peopleCount,
            ]);
        }
        //иначе отобразим Главную страницу сайта
        return $this->render('index');
    }
    
    /**
     * Контроллер для страницы "Зачем страховать"
     * @return type
     */
    public function actionWhyAnswer() {
        $this->layout = 'layout_main';
        return $this->render('why-answer', []);
    }

    /**
     * Контроллер для страницы "Страховой случай"
     * @return type
     */
    public function actionInsuranceCase() {
        $this->layout = 'layout_main';
        return $this->render('insurance-case', []);
    }
    
    /**
     * Контроллер для страницы "правила страхования"
     */
    public function actionInsuranceRegulations() {
        $this->layout = 'layout_main';
        return $this->render('insurance-regulations', []);
    }
    
    /**
     * Контроллер для страницы "Вопросы и ответы"
     */
    public function actionQuestionAnswer() {
        $this->layout = 'layout_main';
        return $this->render('question-answer', []);
    }
    
    /**
     * Контроллер для страницы "Контакты"
     */
    public function actionContacts() {
        $this->layout = 'layout_main';
        return $this->render('contacts', []);
    }
    
    public function actionAbout() {
        $this->layout = "layout_main";
        return $this->render("about", []);
    }
    
    /**
     * Контролер формы "перезвоните мне"
     * будет сохранять введенный данные в БД
     */
    public function actionCallMe() {
        if(Yii::$app->request->isAjax) {
            
            //в данной ситуации не будем задавать ответ в "правильном виде" т.к. после того как всё правильно делаешь, 
            //происходит отсылание на страницу заданную в action (т.е. сюда)
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            
            $post = Yii::$app->request->post();
            if(!isset($post['CallMeForm'])) {
                return 0;
            }
            
            if(!isset($post['_csrf']) || !isset($post['CallMeForm']['crlf'])) {
                return -1;
            }
            
            if($post['_csrf'] != $post['CallMeForm']['crlf']) {
                return -2;
            }
            
            //загрузим модель
            $modelCM = new \common\models\CallMeForm();
            $modelCM->load($post);
            
            $modelCM->save();
            
            return "ok";
        }
    }
}
