<?php
/**
 * @var \omnilight\scheduling\Schedule $schedule
 */

//use console\components\tasks\CacheUpdaterTask;
use console\components\tasks\TaskProcess;
use common\models\lib\Crons;
use common\models\lib\CronTasks;
use Cron\CronExpression;

echo ' -- Sheduler start -- ' . date('r') . "\n";

$tasks = CronTasks::find()
    ->select([
        'ct.*',                
    ])
    ->from(['ct' => CronTasks::tableName()])
    ->where('ct.active')
    ->andWhere("array_length(regexp_split_to_array(shedule, ' '),1)=5")            	         
    ->asArray()
    ->all();

//var_dump($tasks);    
foreach ($tasks as $task) {	

    if (!empty($task['process_id'])) {
        echo "Task '{$task['task_class']}' already running, skipping\n";
        continue;
    }

    /* если крон не отработал вовремя, то запускаем его  */
    $cronExpr = CronExpression::factory($task['shedule']);
    $lastTime = !empty($task['last_time']) ? strtotime($task['last_time']) : 0;
    $prevTime = $cronExpr->getPreviousRunDate()->getTimestamp();
    //echo "Prev: ", date('r', $prevTime), "\n";
    //echo "Last: ", date('r', $lastTime), "\n";
    //echo "Next: ", $cronExpr->getNextRunDate()->format('r'), "\n\n";

    if ($prevTime > $lastTime) {
        echo "Manual start task '{$task['task_class']}' - last time (" . 
            date('r', $lastTime) . ") less then previos run time (" . date('r', $prevTime) . ") ", "\n";
        $task['shedule'] = '* * * * *';
    }
    
    $command = PHP_BINARY . ' '. dirname(\Yii::getAlias('@app')) 
        . '/yii tasks-manager/run --taskId=' . $task['id'];

    $event = $schedule->exec($command);
    $event->cron($task['shedule']);    
    //var_dump($event);	
}

