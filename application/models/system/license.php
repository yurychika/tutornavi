<?php

class System_License_Model extends Model
{
	public function getLicense()
	{
		$row = $this->db->query("SELECT * FROM `:prefix:core_config` WHERE `plugin`='system' AND `keyword`='license' LIMIT 1")->row();

		if ( !$row || !( $row = json_decode($row['val'], true) ) )
		{
			$row = false;
		}

		return $row;
	}

	public function changeLicense($str)
	{
		if ( !( $license = $this->getLicense() ) || !is_array($license) )
		{
			$license = array();
		}

		$license['license'] = $str;

		$this->db->update('core_config', array('val' => json_encode($license)), array('plugin' => 'system', 'keyword' => 'license'), 1);

		$this->cache->cleanup('kaboom');
	}
}
