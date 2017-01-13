<?php

namespace console\components\tasks;
use Yii;

abstract class ShedulerTaskAbstract {

	protected $_task;	

	public function __construct($task, $params = []) {
		$this->_task = $task;

		if (!empty($this->_task->process_id)) {
			$this->addLog('Task already running in process with Id: ' . $this->_task->process_id . ' ' . date('r'));
			return;
		}

		$this->_task->process_id = getmypid();
		$this->_task->save(false, ['process_id']);
		$this->addLog('Task running with process Id: ' . $this->_task->process_id . ' ' . date('r'));
		
		$this->run($params);

		$this->addLog('Task ending. ' . date('r'));
		$this->markEndOfTask();
	}

	public abstract function run();

	protected function markEndOfTask() {
		$this->_task->process_id = 0;
		$this->_task->last_time = date('Y-m-d H:i:s');		
		$this->_task->save(false, ['process_id', 'last_time']);
	}

	public function addLog($message, $mailing = false) {
		echo "{$message}\n";		

		$logPath = self::getTaskLogFilePath($this->_task);		

		if (!file_exists($logPath)) {
			file_put_contents($logPath, '');
			chmod($logPath, 0666);
		}

		file_put_contents($logPath, $message . "\n", FILE_APPEND);				

		if ($mailing && is_array(Yii::$app->params['taskNotifyEmails'])) {			
			//Yii::$app->mailer->useFileTransport = true;
			$messages = [];
			foreach (Yii::$app->params['taskNotifyEmails'] as $email) {				
				$messages[] = Yii::$app->mailer->compose()
			    ->setFrom('bot@carsrent.com')
			    ->setTo($email)
			    ->setSubject('Task ' . $this->_task->task_class . ' Notify')
			    ->setTextBody($message)
			    ->setHtmlBody($message);	
			}
			Yii::$app->mailer->sendMultiple($messages);
		}
	}	

	public static function getTaskLogFilePath($task) {
		return \Yii::getAlias('@console/runtime/logs/tasks/' . $task->task_class . '.log');		
	}
}
