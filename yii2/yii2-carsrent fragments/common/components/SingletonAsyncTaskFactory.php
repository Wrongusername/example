<?php

namespace common\components;
use yii\mutex\FileMutex;
use common\components\AsyncTaskFactory;


class SingletonAsyncTaskFactory extends AsyncTaskFactory
{
    const WORKERSUFFIX='_Worker';

    /**
     * SingletonAsyncTask constructor.
     * @param $mutexname
     * @param $action
     * @param array $args
     */
    public static function getTask($mutexname, $action, array $args=[])
    {
        $workerMutexName=$mutexname . '_' . self::WORKERSUFFIX;

        if (!\Yii::$app->mutex->acquire($mutexname))
        {
            return new SingletonAsyncTask($workerMutexName);
        }

        array_unshift($args, "--mutexname={$workerMutexName}");
        $asyncTaskRes=AsyncTaskFactory::getTask($action,$args);
        if ($asyncTaskRes === false) //случилось что-то катастрофическое не давшее задаче стартовать
        {
            \Yii::$app->mutex->release($mutexname);
            return false;
        }
        if ($asyncTaskRes === 0) //задача не стартовала т.как уже запущен такой рабочий
        {
            return new SingletonAsyncTask($workerMutexName,$mutexname);
        }

        return new SingletonAsyncTask($workerMutexName,$mutexname,$asyncTaskRes);
    }

    public static function getLock($mutexname)
    {

    }

}