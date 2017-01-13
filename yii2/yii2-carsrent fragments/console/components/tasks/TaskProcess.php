<?php

namespace console\components\tasks;

/**
* 
*/
class TaskProcess
{
	protected $_task;
	protected $_pid = 0;
	private $_resource = null;
	protected $_pipes = array();

	function __construct($task) {
		$this->_task = $task;
	}

	public function getPid() {
		return $this->_pid;
	}

	public function start() {
		$command = PHP_BINARY . ' yii task-manager/run --taskId=' . $this->_task['id'];
	    echo 'Open process: ' . $command . "\r\n";

	    $descriptorspec = array(
            0 => array('pipe', 'r'),  // stdin
            1 => array('pipe', 'w'),  // stdout
            2 => array('pipe', 'w') // stderr 
        );

        $this->_resource = proc_open($command, $descriptorspec, $this->_pipes);

        if (!is_resource($this->_resource)) {
    		return false;
    	}

    	$proc_status = proc_get_status($this->_resource);
        $this->_pid = isset($proc_status['pid']) ? $proc_status['pid'] : 0;

        return $this->_resource;
	}


}