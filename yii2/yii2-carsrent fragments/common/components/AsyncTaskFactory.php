<?php
/**
 * Created by PhpStorm.
 * User: Whatever
 * Date: 8/1/2016
 * Time: 4:27 PM
 */

namespace common\components;
use common\components\AsyncTask;


class AsyncTaskFactory
{
    public static function getTask($action,array $args=[])
    {
        $basePath=dirname(\Yii::getAlias('@app'));
        if (!empty($args)) {
            foreach ($args as $k => $arg) {
                if (!is_scalar($arg)) {
                    throw new \yii\base\Exception("Аргумент $arg не скалярного типа и не может быть передан в фоновую задачу напрямую.");
                }
                if (is_bool($arg)) {
                    $args[$k] = (int)$arg;
                }
            }
        }

        $command='php ' . $basePath . "/yii async-task/$action" . (!empty($args)? ' ' . implode(' ',$args):'');
        $readpipe=popen($command, 'r');

        if (!is_resource($readpipe))
        {
            throw new \yii\base\Exception( "Не удалось запустить асинхронную задачу" );
        }

        stream_set_blocking($readpipe,false);
        $pipes=[$readpipe];

        $n=null;
        $read = $pipes;
        stream_select($read, $n, $n, null);


        $results = stream_get_contents($readpipe);

        if (!in_array($results,[0,1]) || feof($readpipe))
        {
            return false;
        }
        if ($results === 0)
        {
            return 0;
        }

        return new AsyncTask($pipes);
    }
}