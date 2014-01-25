<?php

class Comments_Comments_Model extends Model
{
	public function saveComment($commentID, $comment, $resource = '', $userID = 0, $itemID = 0, $table = '', $column = '')
	{
		// Is this a new comment?
		if ( !$commentID )
		{
			// Get resource ID
			if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) )
			{
				return false;
			}

			// Comment data
			$comment['resource_id'] = $resourceID;
			$comment['user_id'] = $userID;
			$comment['item_id'] = $itemID;
			$comment['poster_id'] = session::item('user_id');
			$comment['post_date'] = date_helper::now();

			// Get table and column names
			$table = $table ? $table : config::item('resources', 'core', $resource, 'table');
			$column = $column ? $column : config::item('resources', 'core', $resource, 'column');

			// Insert comment
			if ( $commentID = $this->db->insert('core_comments', $comment) )
			{
				// Update comment count
				$this->db->query("UPDATE `:prefix:" . $table . "` SET `total_comments`=`total_comments`+1 WHERE `" . $column . "`=? LIMIT 1", array($itemID));

				// Action hook
				hook::action('comments/insert', session::item('user_id'), $resourceID, $userID, $itemID, $comment);

				// Do we have user id?
				if ( $userID && $userID != session::item('user_id') )
				{
					// Save notification
					timeline_helper::notice($resource . '_comment', $userID, session::item('user_id'), $itemID);
				}
			}
		}
		// This is an existing comment
		else
		{
			$this->db->update('core_comments', $comment, array('comment_id' => $commentID), 1);

			// Action hook
			hook::action('comments/update', $commentID, $comment);
		}

		return $commentID;
	}

	public function deleteComment($commentID, $resource, $userID, $posterID, $itemID, $table = '', $column = '')
	{
		// Get resource ID
		if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) )
		{
			return false;
		}

		// Get table and column names
		$table = $table ? $table : config::item('resources', 'core', $resource, 'table');
		$column = $column ? $column : config::item('resources', 'core', $resource, 'column');

		// Delete comment
		$retval = $this->db->delete('core_comments', array('comment_id' => $commentID, 'resource_id' => $resourceID, 'item_id' => $itemID), 1);
		if ( $retval )
		{
			// Update comment count
			$this->db->query("UPDATE `:prefix:" . $table . "` SET `total_comments`=`total_comments`-1 WHERE `" . $column . "`=? LIMIT 1", array($itemID));

			// Action hook
			hook::action('comments/delete', $commentID, $resourceID, $userID, $posterID, $itemID);
		}

		return $retval;
	}

	public function deleteComments($resource, $itemID, $limit = false)
	{
		// Get resource ID
		if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) || !$itemID )
		{
			return false;
		}

		if ( !is_array($itemID) )
		{
			$itemID = array($itemID);
		}

		$commentIDs = array();

		// Get comment IDs
		$result = $this->db->query("SELECT * FROM `:prefix:core_comments` WHERE `resource_id`=? AND `item_id` IN (?)", array($resourceID, $itemID))->result();
		foreach ( $result as $comment )
		{
			$commentIDs[] = $comment['comment_id'];
		}

		// Delete reports
		loader::model('reports/reports');
		$this->reports_model->deleteReports('comment', $commentIDs);

		// Delete comments
		if ( $retval = $this->db->delete('core_comments', array('resource_id' => $resourceID, 'item_id' => $itemID), $limit) )
		{
			// Action hook
			hook::action('comments/delete_multiple', $resourceID, $itemID);
		}

		return $retval;
	}

	public function deleteUser($userID, $user)
	{
		$data = array();

		$result = $this->db->query("SELECT * FROM `:prefix:core_comments` WHERE `poster_id`=?", array($userID))->result();

		foreach ( $result as $comment )
		{
			// Get resource ID
			if ( !( $resource = config::item('resources', 'core', $comment['resource_id']) ) )
			{
				return false;
			}

			if ( !isset($data[$resource]) )
			{
				$data[$resource] = array(
					'resource' => $comment['resource_id'],
					'table' => config::item('resources', 'core', $resource, 'table'),
					'column' => config::item('resources', 'core', $resource, 'column'),
					'items' => array(),
				);
			}

			if ( !isset($data[$resource]['items'][$comment['item_id']]) )
			{
				$data[$resource]['items'][$comment['item_id']] = 0;
			}

			$data[$resource]['items'][$comment['item_id']]++;
		}

		foreach ( $data as $resource => $items )
		{
			foreach ( $items['items'] as $itemID => $total )
			{
				// Update comment count
				$this->db->query("UPDATE `:prefix:" . $items['table'] . "` SET `total_comments`=`total_comments`-? WHERE `" . $items['column'] . "`=? LIMIT 1", array($total, $itemID));
			}
		}

		// Delete comments
		$retval = $this->db->query("DELETE FROM `:prefix:core_comments` WHERE `user_id`=? OR `poster_id`=?", array($userID, $userID));

		// Action hook
		hook::action('comments/delete_user', $userID, $user);

		return $retval;
	}

	public function getComment($commentID, $params = array())
	{
		$comment = $this->fields_model->getRow('comment', $commentID, false, $params);

		// Escape comments
		if ( $comment && ( !isset($params['escape']) || $params['escape'] ) )
		{
			$comment['comment'] = text_helper::entities($comment['comment']);
		}

		return $comment;
	}

	public function countRecentComments()
	{
		$time = date_helper::now() - session::permission('comments_delay_time', 'comments') * ( session::permission('comments_delay_type', 'comments') == 'minutes' ? 60 : 3600 );

		$comments = $this->db->query("SELECT COUNT(*) AS `totalrows`
			FROM `:prefix:core_comments`
			WHERE `poster_id`=? AND `post_date`>?",
			array(session::item('user_id'), $time))->row();

		return $comments['totalrows'];
	}

	public function countComments($columns = array(), $items = array(), $params = array())
	{
		$resource = isset($params['resource']) ? $params['resource'] : '';
		$itemID = isset($params['item_id']) ? $params['item_id'] : 0;
		$table = isset($params['table']) ? $params['table'] : '';
		$column = isset($params['column']) ? $params['column'] : '';

		// Do we have resource name?
		if ( $resource && $resourceID = config::item('resources', 'core', $resource, 'resource_id') && $itemID )
		{
			// Get resource ID
			$resourceID = config::item('resources', 'core', $resource, 'resource_id');

			// Get table and column names
			$table = $table ? $table : config::item('resources', 'core', $resource, 'table');
			$column = $column ? $column : config::item('resources', 'core', $resource, 'column');

			// Get comment count
			$comments = $this->db->query("SELECT `total_comments` FROM `:prefix:$table` WHERE `$column`=? LIMIT 1", array($itemID))->row();;

			$total = $comments ? $comments['total_comments'] : 0;
		}
		else
		{
			$params['count'] = 1;

			$total = $this->getComments($resource, $itemID, array(), false, false, $params);

			return $total;
		}

		return $comments ? $comments['total_comments'] : 0;
	}

	public function getComments($resource = '', $itemID = 0, $columns, $order = false, $limit = 15, $params = array())
	{
		// Do we have resource name?
		if ( $resource && $resourceID = config::item('resources', 'core', $resource, 'resource_id') )
		{
			// Get resource ID
			$resourceID = config::item('resources', 'core', $resource, 'resource_id');

			// Add condition
			$columns[] = "`c`.`resource_id`=" . $this->db->escape($resourceID);

			// Do we have an item ID?
			if ( $itemID )
			{
				$columns[] = "`c`.`item_id`=" . $this->db->escape($itemID);
			}
		}

		// Do we need to count comments?
		if ( isset($params['count']) && $params['count'] )
		{
			$total = $this->fields_model->countRows('comment', true, $columns, array(), $params);

			return $total;
		}

		// Get comments
		$comments = $this->fields_model->getRows('comment', true, false, $columns, array(), $order, $limit, $params);

		// Escape comments
		if ( !isset($params['escape']) || $params['escape'] )
		{
			foreach ( $comments as $index => $comment )
			{
				$comments[$index]['comment'] = text_helper::entities($comment['comment']);
			}
		}

		return $comments;
	}

	public function getReportedActions()
	{
		$actions = array(
			'delete' => __('report_item_delete', 'reports'),
		);

		return $actions;
	}

	public function runReportedAction($commentID, $action)
	{
		$comment = $this->getComment($commentID);

		if ( $comment )
		{
			$resource = config::item('resources', 'core', $comment['resource_id']);

			if ( $resource )
			{
				if ( $action == 'delete' )
				{
					$this->deleteComment($commentID, $resource, $comment['user_id'], $comment['poster_id'], $comment['item_id']);
				}
			}
		}

		return true;
	}

	public function getReportedURL($commentID)
	{
		$url = 'cp/plugins/comments/edit/' . $commentID;

		return $url;
	}
}
