<?php
/**
 * Created by PhpStorm.
 * User: Whatever
 * Date: 7/29/2016
 * Time: 12:41 PM
 */

namespace common\components;
use common\components\AsyncTask;
use yii\mutex\FileMutex;

class SingletonAsyncTask
{
    protected $_workerMutexName;
    protected $_mutex;
    protected $_mutexname;
    protected $_asyncTask;


    public function __construct($workerMutexName,$mutexname=false,AsyncTask $asyncTask=NULL)
    {

        $this->_workerMutexName = $workerMutexName;
        $this->_mutexname=$mutexname;
        $this->_asyncTask=$asyncTask;
    }

    public function running($sectimeout=0,$usectimeout=0)
    {
        if ($this->_asyncTask)
        {
            $running=$this->_asyncTask->running($sectimeout,$usectimeout);
        }
        else
        {
            usleep($sectimeout*1000000+$usectimeout);
            $running=!\Yii::$app->mutex->acquire($this->_workerMutexName);
        }

        if ($running)
        {
            return true;
        }
        else
        {

            if (!$this->_asyncTask)
            {
                \Yii::$app->mutex->release($this->_workerMutexName);
            }
            if (!empty($this->_mutexname)) {
                \Yii::$app->mutex->release($this->_mutexname);
            }
            return false;
        }
    }
}