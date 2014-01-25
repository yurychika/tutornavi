<?php

class System_Counters_Model extends Model
{
	public function countData($resource, $userID, $itemID, $columns, $items, $params, $expiration = 60)
	{
		// Get resource
		$resource = config::item('resources', 'core', $resource);

		// Do we have cached counters?
		if ( !$expiration || !( $counter = $this->getCounters($resource['keyword'], $userID, $itemID) ) || !isset($counter[$resource['keyword']]) )
		{
			// Do we need to update existing cache?
			$update = $expiration && $counter ? 1 : 0;

			// Count data
			$counter[$resource['keyword']] = $total = $this->{$resource['model'].'_model'}->{'count'.$resource['items']}($columns, $items, $params);

			// Save cache
			if ( $expiration && config::item('max_cache_results', 'system') && $total >= config::item('max_cache_results', 'system') )
			{
				$this->saveCounters($update, $resource['keyword'], $userID, $itemID, $counter, $expiration);
			}
		}
		else
		{
			$total = $counter[$resource['keyword']];
		}

		return $total;
	}

	public function saveCounters($update, $resource, $userID, $itemID, $data = array(), $expiration = 60)
	{
		// Get resource ID
		$resourceID = config::item('resources', 'core', $resource, 'resource_id');

		if ( !$userID )
		{
			$userID = 0;
		}

		if ( $update )
		{
			$this->db->update('core_counters', array('data' => json_encode($data)), array('resource_id' => $resourceID, 'user_id' => $userID, 'item_id' => $itemID), 1);
		}
		else
		{
			$counter = array(
				'resource_id' => $resourceID,
				'user_id' => $userID,
				'item_id' => $itemID,
				'data' => json_encode($data),
				'post_date' => date_helper::now(),
				'expiration' => $expiration,
			);

			$this->db->insert('core_counters', $counter);
		}

		return true;
	}

	public function getCounters($resource, $userID, $itemID)
	{
		// Get resource ID
		$resourceID = config::item('resources', 'core', $resource, 'resource_id');

		$counter = $this->db->query("SELECT `resource_id`, `user_id`, `item_id`, `data`
			FROM `:prefix:core_counters`
			WHERE `resource_id`=? AND `user_id`=? AND `item_id`=? LIMIT 1", array($resourceID, $userID, $itemID))->row();

		if ( $counter )
		{
			$counter = @json_decode($counter['data'], true);

			if ( !is_array($counter) )
			{
				$counter = array();
				$this->db->delete('core_counters', array('user_id' => $userID, 'resource_id' => $resourceID, 'item_id' => $itemID), 1);
			}
			// Counter exists but is empty
			elseif ( !$counter )
			{
				$counter['___blank'] = true;
			}
		}

		return $counter;
	}

	public function deleteCounters($resource, $itemID)
	{
		// Get resource ID
		$resourceID = config::item('resources', 'core', $resource, 'resource_id');

		$reval = $this->db->delete('core_counters', array('resource_id' => $resourceID, 'item_id' => $itemID), is_array($itemID) ? count($itemID) : 1);

		return $reval;
	}

	public function cleanup()
	{
		$this->db->query("DELETE FROM `:prefix:core_counters` WHERE (`post_date`+`expiration`*60)<?", array(date_helper::now()));

		$this->cron_model->addLog('[System] Deleted expired search counters.');
	}
}
