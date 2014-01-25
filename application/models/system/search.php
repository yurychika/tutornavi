<?php

class System_Search_Model extends Model
{
	public function saveSearch($conditions = array(), $values = array(), $results = 0)
	{
		$data = array(
			'search_id' => text_helper::random(8),
			'user_id' => session::item('user_id'),
			'conditions' => json_encode($conditions),
			'values' => json_encode($values),
			'results' => $results,
			'post_date' => date_helper::now(),
		);
		if ( !session::item('user_id') )
		{
			$data['ip_address'] = input::ipaddress();
		}

		$this->db->insert('core_search', $data);

		return $data['search_id'];
	}

	public function getSearch($search_id)
	{
		$search = $this->db->query("SELECT `search_id`, `user_id`, `ip_address`, `conditions`, `values`, `results`, `post_date`
			FROM `:prefix:core_search`
			WHERE `search_id`=? AND `" . (session::item('user_id') ? "user_id" : "ip_address") . "`=? LIMIT 1",
			array($search_id, ( session::item('user_id') ? session::item('user_id') : input::ipaddress() )))->row();

		if ( $search )
		{
			$search['conditions'] = @json_decode($search['conditions'], true);
			if ( !is_array($search['conditions']) )
			{
				$search['conditions'] = array();
			}
			$search['values'] = @json_decode($search['values'], true);
			if ( !is_array($search['values']) )
			{
				$search['values'] = array();
			}
		}

		return $search;
	}

	public function searchData($resource, $fields, $columns, $values, $params = array())
	{
		// Parse filter values
		if ( $fields )
		{
			list($_columns, $items, $_values) = $this->fields_model->parseSearch($resource, $fields, $params);

			// Merge values
			$columns = $columns && is_array($columns) ? array_merge($_columns, $columns) : $_columns;
			$values = $values && is_array($values) ? array_merge($_values, $values) : $_values;
		}
		else
		{
			$items = $columns['items'];
			$columns = $columns['columns'];
		}

		// Assign vars
		view::assign(array('values' => $values));

		// Do we have any values?
		if ( $values )
		{
			// Count data
			$total = $this->counters_model->countData($resource, 0, 0, $columns, $items, $params, 0);

			if ( $total )
			{
				// Save search
				$searchID = $this->search_model->saveSearch(array('columns' => $columns, 'items' => $items), $values, $total);

				// Return search ID
				return $searchID;
			}
			// No results were found
			else
			{
				return 'no_results';
			}
		}
		else
		{
			return 'no_terms';
		}
	}

	public function deleteSearch($search_id)
	{
		$retval = $this->db->delete('core_search', array('search_id' => $search_id, (session::item('user_id') ? "user_id" : "ip_address") => (session::item('user_id') ? session::item('user_id') : input::ipaddress())), 1);

		return $retval;
	}

	public function validateRequest($search_id)
	{
		if ( strlen($search_id) != 8 || preg_match('/[^a-zA-Z0-9]/', $search_id) )
		{
			return false;
		}

		return true;
	}

	public function prepareValue($value, $prefix, $columns = array())
	{
		if ( !is_array($columns) )
		{
			if ( $columns == 'user' )
			{
				return $this->prepareUser($value);
			}

			$columns = array($columns);
		}

		$str = array();

		foreach ( $columns as $column )
		{
			$str[] = "`" . $prefix . "`.`" . $column . "` LIKE '" . trim($this->db->escape($value, true), "'") . "'";
		}

		$str = "(" . implode(' OR ', $str) . ")";

		return $str;
	}

	public function prepareUser($value)
	{
		if ( is_numeric($value) )
		{
			$str = "`u`.`user_id`=" . $this->db->escape($value);
		}
		else
		{
			$value = trim($this->db->escape($value, true), "'");

			$str = "(`u`.`email` LIKE '" . $value . "' OR `u`.`username` LIKE '" . $value . "' OR `u`.`name1` LIKE '" . $value . "' OR `u`.`name2` LIKE '" . $value . "')";
		}

		return $str;
	}

	public function cleanup()
	{
		$this->db->query("DELETE FROM `:prefix:core_search` WHERE `post_date`<?", array(date_helper::now() - 60*60*12));
		$this->cron_model->addLog('[System] Deleted expired search queries.');
	}
}
