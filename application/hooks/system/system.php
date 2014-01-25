<?php

class System_System_Hook extends Hook
{
	public function cronRun()
	{
		$this->counters_model->cleanup();
		$this->search_model->cleanup();

		loader::model('system/requests');
		$this->requests_model->cleanup();

		return true;
	}
}
