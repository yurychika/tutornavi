<?php

class System_Resources_Model extends Model
{
	public function getResources()
	{
		$resources = array();
		foreach ( $this->db->query("SELECT * FROM `:prefix:core_resources` ORDER BY keyword ASC")->result() as $resource )
		{
			$resources[$resource['keyword']] = $resource;
			$resources[$resource['resource_id']] = $resource['keyword'];
		}

		return $resources;
	}
}
