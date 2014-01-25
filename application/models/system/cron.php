<?php

class System_Cron_Model extends Model
{
	protected $log = array();

	public function addLog($str)
	{
		$this->log[] = $str;
	}

	public function getLog()
	{
		return $this->log;
	}

	public function finalize()
	{
		$this->db->update('core_config', array('val' => date('Ymd', date_helper::now())), array('plugin' => 'system', 'keyword' => 'cron_last_run'), 1);

		return true;
	}
}
