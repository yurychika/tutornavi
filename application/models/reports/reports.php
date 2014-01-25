<?php

class Reports_Reports_Model extends Model
{
	public function saveReport($resourceID, $userID, $itemID, $subjectID, $message)
	{
		// Report data
		$report = array(
			'poster_id' => session::item('user_id'),
			'resource_id' => $resourceID,
			'user_id' => $userID,
			'item_id' => $itemID,
			'subject_id' => $subjectID,
			'message' => $message,
			'post_date' => date_helper::now(),
		);

		// Insert report
		if ( $reportID = $this->db->insert('reports', $report) )
		{
			// Action hook
			hook::action('reports/insert', session::item('poster_id'), $resourceID, $userID, $itemID, $subjectID, $message);
		}

		return $reportID;
	}

	public function getReport($reportID)
	{
		$report = $this->db->query("SELECT * FROM `:prefix:reports` WHERE `report_id`=? LIMIT 1", array($reportID))->row();

		return $report;
	}

	public function isReported($resourceID, $itemID)
	{
		$report = $this->db->query("SELECT COUNT(*) AS `totalrows` FROM `:prefix:reports` WHERE `poster_id`=? AND `resource_id`=? AND `item_id`=?", array(session::item('user_id'), $resourceID, $itemID))->row();

		return $report['totalrows'] ? true : false;
	}

	public function getUserID($resource, $itemID, $table = '', $column = '')
	{
		// Get table and column names
		$user = $table ? $table : config::item('resources', 'core', $resource, 'user');
		$table = $table ? $table : config::item('resources', 'core', $resource, 'table');
		$column = $column ? $column : config::item('resources', 'core', $resource, 'column');

		$report = $this->db->query("SELECT `" . $user . "` AS `user_id` FROM `:prefix:" . $table . "` WHERE `" . $column . "`=?", array($itemID))->row();

		return $report ? $report['user_id'] : 0;
	}

	public function deleteReport($reportID)
	{
		// Delete report
		$retval = $this->db->delete('reports', array('report_id' => $reportID), 1);
		if ( $retval )
		{
			// Action hook
			hook::action('reports/delete', $reportID);
		}

		return $retval;
	}

	public function deleteReports($resource, $itemID)
	{
		// Get resource ID
		if ( !$itemID || !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) )
		{
			return false;
		}

		// Delete reports
		$retval = $this->db->delete('reports', array('resource_id' => $resourceID, 'item_id' => $itemID));

		if ( $retval )
		{
			// Action hook
			hook::action('reports/delete_multiple', $resourceID, $itemID);
		}

		return $retval;
	}

	public function deleteUser($userID, $user)
	{
		// Delete reports
		$retval = $this->db->query("DELETE FROM `:prefix:reports` WHERE `user_id`=? OR `poster_id`=?", array($userID, $userID));

		if ( $retval )
		{
			// Action hook
			hook::action('reports/delete_user', $userID, $user);
		}

		return $retval;
	}

	public function countReports($columns = array(), $items = array(), $params = array())
	{
		$resource = isset($params['resource']) ? $params['resource'] : '';

		$params['count'] = 1;

		$total = $this->getReports($resource, array(), false, false, $params);

		return $total;
	}

	public function getReports($resource = '', $columns, $order = false, $limit = 15, $params = array())
	{
		// Do we have resource name?
		if ( $resource && $resourceID = config::item('resources', 'core', $resource, 'resource_id') )
		{
			// Get resource ID
			$resourceID = config::item('resources', 'core', $resource, 'resource_id');

			// Add condition
			$columns[] = "`r`.`resource_id`=" . $this->db->escape($resourceID);
		}

		// Do we need to count reports?
		if ( isset($params['count']) && $params['count'] )
		{
			$total = $this->fields_model->countRows('report', true, $columns, array(), $params);

			return $total;
		}

		// Get reports
		$reports = $this->fields_model->getRows('report', true, false, $columns, array(), $order, $limit, $params);

		// Escape reports
		if ( !isset($params['escape']) || $params['escape'] )
		{
			foreach ( $reports as $index => $report )
			{
				$reports[$index]['message'] = text_helper::entities($report['message']);
			}
		}

		return $reports;
	}
}
