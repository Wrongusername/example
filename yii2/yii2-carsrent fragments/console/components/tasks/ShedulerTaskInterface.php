<?php

namespace console\components\tasks;

interface ShedulerTaskInterface {
	public function run($params = []);
}
