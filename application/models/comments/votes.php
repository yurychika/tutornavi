<?php

class Comments_Votes_Model extends Model
{
	public function getVote($resource, $itemID)
	{
		// Get resource ID
		if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) )
		{
			return false;
		}

		// Default vote array
		$vote = array('score' => 0, 'post_date' => 0);

		// Get vote
		if ( !users_helper::isLoggedin() )
		{
			return $vote;
		}

		$item = $this->db->query("SELECT `score`, `post_date`
			FROM `:prefix:core_votes`
			WHERE `resource_id`=? AND `item_id`=? AND `user_id`=? LIMIT 1",
			array($resourceID, $itemID, session::item('user_id')))->row();

		if ( $item )
		{
			return $item;
		}

		return $vote;
	}

	public function getVotes($resource, $itemIDs)
	{
		// Default vote array
		$votes = array();

		// Are we logged in?
		if ( !users_helper::isLoggedin() )
		{
			return $vote;
		}

		// Get resource ID
		if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) )
		{
			return false;
		}

		$items = $this->db->query("SELECT `score`, `post_date`, `item_id`
			FROM `:prefix:core_votes`
			WHERE `resource_id`=? AND `item_id` IN (" . implode(",", $itemIDs) . ") AND `user_id`=? LIMIT ?",
			array($resourceID, session::item('user_id'), count($itemIDs)))->result();

		foreach ( $items as $item )
		{
			$votes[$item['item_id']]['score'] = $item['score'];
			$votes[$item['item_id']]['post_date'] = $item['post_date'];
		}

		return $votes;
	}

	public function getMultiVotes($data)
	{
		// Default like array
		$votes = array();

		// Do we have data and are we logged in?
		if ( !$data || !users_helper::isLoggedin() )
		{
			return $votes;
		}

		// Create columns
		$columns = array();
		foreach ( $data as $resource => $items )
		{
			if ( $items && ( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) )
			{
				$columns[] = "`resource_id`=" . (int)$resourceID . " AND `item_id` IN (" . implode(",", $items) . ")";
			}
		}

		// Any columns?
		if ( !$columns )
		{
			return $votes;
		}

		$items = $this->db->query("SELECT `resource_id`, `score`, `post_date`, `item_id`
			FROM `:prefix:core_votes`
			WHERE (" . implode(" OR ", $columns) . ") AND `user_id`=?",
			array(session::item('user_id')))->result();

		foreach ( $items as $item )
		{
			$votes[$item['resource_id']][$item['item_id']]['score'] = $item['score'];
			$votes[$item['resource_id']][$item['item_id']]['post_date'] = $item['post_date'];
		}

		return $votes;
	}

	public function getResourceVote($resource, $itemID, $table = '', $column = '', $user = '')
	{
		// Get resource ID
		if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) )
		{
			return false;
		}

		// Get table and column names
		$table = $table ? $table : config::item('resources', 'core', $resource, 'table');
		$column = $column ? $column : config::item('resources', 'core', $resource, 'column');
		$user = $user ? $user : config::item('resources', 'core', $resource, 'user');

		// Get resource item and score if exists
		$item = $this->db->query("SELECT `r`.`total_votes`, `r`.`total_score`" . ( $user ? ", `r`.`" . $user . "` AS `user_id`" : "" ) . ", `r`.`total_rating`, `v`.`post_date`, `v`.`score`
			FROM `:prefix:" . $table . "` AS `r` LEFT JOIN `:prefix:core_votes` AS `v` ON `r`.`" . $column . "`=`v`.`item_id` AND `v`.`resource_id`=? AND `v`.`user_id`=?
			WHERE `r`.`" . $column . "`=? LIMIT 1", array($resourceID, session::item('user_id'), $itemID))->row();

		return $item;
	}

	public function saveVote($resource, $userID, $itemID, $score, $table = '', $column = '')
	{
		// Get resource ID
		if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) )
		{
			return false;
		}

		// Get table and column names
		$table = $table ? $table : config::item('resources', 'core', $resource, 'table');
		$column = $column ? $column : config::item('resources', 'core', $resource, 'column');

		$data = array(
			'resource_id' => $resourceID,
			'item_id' => $itemID,
			'user_id' => session::item('user_id'),
			'score' => $score,
			'post_date' => date_helper::now(),
		);

		// Update total votes and score
		$retval = $this->db->query("UPDATE `:prefix:" . $table . "`
			SET `total_votes`=`total_votes`+1, `total_score`=`total_score`+?, `total_rating`=`total_score`/`total_votes`
			WHERE `" . $column . "`=? LIMIT 1",
			array($score, $itemID));

		if ( $retval )
		{
			// Save vote
			$this->db->insert('core_votes', $data);

			// Action hook
			hook::action('votes/insert', session::item('user_id'), $resourceID, $itemID, $score);

			// Do we have user id?
			if ( $userID != session::item('user_id') )
			{
				// Save notification
				timeline_helper::notice($resource . '_vote', $userID, session::item('user_id'), $itemID);
			}
		}

		return $retval;
	}

	public function deleteVotes($resource, $itemID, $limit = false, $table = '', $column = '')
	{
		// Get resource ID
		if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) || !$itemID )
		{
			return false;
		}

		// Get table and column names
		$table = $table ? $table : config::item('resources', 'core', $resource, 'table');
		$column = $column ? $column : config::item('resources', 'core', $resource, 'column');

		// Delete votes
		if ( $retval = $this->db->delete('core_votes', array('resource_id' => $resourceID, 'item_id' => $itemID), $limit) )
		{
			// Action hook
			hook::action('votes/delete', $resourceID, $itemID);
		}

		return $retval;
	}

	public function deleteUser($userID, $user)
	{
		$data = array();

		$result = $this->db->query("SELECT * FROM `:prefix:core_votes` WHERE `user_id`=?", array($userID))->result();

		foreach ( $result as $vote )
		{
			// Get resource ID
			if ( !( $resource = config::item('resources', 'core', $vote['resource_id']) ) )
			{
				return false;
			}

			$table = config::item('resources', 'core', $resource, 'table');
			$column = config::item('resources', 'core', $resource, 'column');

			// Update total votes and score
			$retval = $this->db->query("UPDATE `:prefix:" . $table . "`
				SET `total_votes`=`total_votes`-1, `total_score`=`total_score`-?, `total_rating`=`total_score`/`total_votes`
				WHERE `" . $column . "`=? LIMIT 1",
				array($vote['score'], $vote['item_id']));
		}

		// Delete votes
		$retval = $this->db->query("DELETE FROM `:prefix:core_votes` WHERE `user_id`=?", array($userID));

		// Action hook
		hook::action('votes/delete_user', $userID, $user);

		return $retval;
	}
}
