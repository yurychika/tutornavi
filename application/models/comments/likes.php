<?php

class Comments_Likes_Model extends Model
{
	public function getLike($resource, $itemID)
	{
		// Get resource ID
		if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) )
		{
			return false;
		}

		// Default like array
		$like = array('post_date' => 0);

		// Get like
		if ( !users_helper::isLoggedin() )
		{
			return $like;
		}

		$item = $this->db->query("SELECT `post_date`
			FROM `:prefix:core_likes`
			WHERE `resource_id`=? AND `item_id`=? AND `user_id`=? LIMIT 1",
			array($resourceID, $itemID, session::item('user_id')))->row();

		if ( $item )
		{
			return $item;
		}

		return $like;
	}

	public function getLikes($resource, $itemIDs)
	{
		// Default like array
		$likes = array();

		// Are we logged in?
		if ( !users_helper::isLoggedin() )
		{
			return $likes;
		}

		// Get resource ID
		if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) )
		{
			return false;
		}

		$items = $this->db->query("SELECT `post_date`, `item_id`
			FROM `:prefix:core_likes`
			WHERE `resource_id`=? AND `item_id` IN (" . implode(",", $itemIDs) . ") AND `user_id`=? LIMIT ?",
			array($resourceID, session::item('user_id'), count($itemIDs)))->result();

		foreach ( $items as $item )
		{
			$likes[$item['item_id']]['post_date'] = $item['post_date'];
		}

		return $likes;
	}

	public function getMultiLikes($data)
	{
		// Default like array
		$likes = array();

		// Do we have data and are we logged in?
		if ( !$data || !users_helper::isLoggedin() )
		{
			return $likes;
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
			return $likes;
		}

		$items = $this->db->query("SELECT `resource_id`, `post_date`, `item_id`
			FROM `:prefix:core_likes`
			WHERE (" . implode(" OR ", $columns) . ") AND `user_id`=?",
			array(session::item('user_id')))->result();

		foreach ( $items as $item )
		{
			$likes[$item['resource_id']][$item['item_id']]['post_date'] = $item['post_date'];
		}

		return $likes;
	}

	public function getResourceLike($resource, $itemID, $table = '', $column = '', $user = '')
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
		$item = $this->db->query("SELECT `r`.`total_likes`" . ( $user ? ", `r`.`" . $user . "` AS `user_id`" : "" ) . ", `l`.`post_date`
			FROM `:prefix:" . $table . "` AS `r` LEFT JOIN `:prefix:core_likes` AS `l` ON `r`.`" . $column . "`=`l`.`item_id` AND `l`.`resource_id`=? AND `l`.`user_id`=?
			WHERE `r`.`" . $column . "`=? LIMIT 1", array($resourceID, session::item('user_id'), $itemID))->row();

		return $item;
	}

	public function saveLike($resource, $userID, $itemID, $like, $table = '', $column = '')
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
		);

		// Like
		if ( $like )
		{
			$data['post_date'] = date_helper::now();

			// Update total likes
			if ( $retval = $this->db->query("UPDATE `:prefix:" . $table . "` SET `total_likes`=`total_likes`+1 WHERE `" . $column . "`=? LIMIT 1", array($itemID)) )
			{
				// Save like
				$this->db->insert('core_likes', $data);

				// Action hook
				hook::action('likes/insert', session::item('user_id'), $resourceID, $itemID);

				// Do we have user id?
				if ( $userID && $userID != session::item('user_id') )
				{
					// Save notification
					timeline_helper::notice($resource . '_like', $userID, session::item('user_id'), $itemID);
				}
			}
		}
		// Unlike
		else
		{
			// Update total likes
			if ( $retval = $this->db->query("UPDATE `:prefix:" . $table . "` SET `total_likes`=`total_likes`-1 WHERE `" . $column . "`=? LIMIT 1", array($itemID)) )
			{
				// Save like
				if ( $this->db->delete('core_likes', $data, 1) )
				{
					// Action hook
					hook::action('likes/unlike', session::item('user_id'), $resourceID, $itemID);

					// Delete notification
					timeline_helper::unnotice($resource . '_like', $userID, session::item('user_id'), $itemID);
				}
			}
		}

		return $retval;
	}

	public function deleteLikes($resource, $itemID, $limit = false, $table = '', $column = '')
	{
		// Get resource ID
		if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) || !$itemID )
		{
			return false;
		}

		// Get table and column names
		$table = $table ? $table : config::item('resources', 'core', $resource, 'table');
		$column = $column ? $column : config::item('resources', 'core', $resource, 'column');

		// Delete likes
		if ( $retval = $this->db->delete('core_likes', array('resource_id' => $resourceID, 'item_id' => $itemID), $limit) )
		{
			// Action hook
			hook::action('likes/delete', $resourceID, $itemID);
		}

		return $retval;
	}

	public function deleteUser($userID, $user)
	{
		$data = array();

		$result = $this->db->query("SELECT * FROM `:prefix:core_likes` WHERE `user_id`=?", array($userID))->result();

		foreach ( $result as $like )
		{
			// Get resource ID
			if ( !( $resource = config::item('resources', 'core', $like['resource_id']) ) )
			{
				return false;
			}

			$table = config::item('resources', 'core', $resource, 'table');
			$column = config::item('resources', 'core', $resource, 'column');

			// Update total likes
			$retval = $this->db->query("UPDATE `:prefix:" . $table . "` SET `total_likes`=`total_likes`-1 WHERE `" . $column . "`=? LIMIT 1", array($like['item_id']));
		}

		// Delete likes
		$retval = $this->db->query("DELETE FROM `:prefix:core_likes` WHERE `user_id`=?", array($userID));

		// Action hook
		hook::action('likes/delete_user', $userID, $user);

		return $retval;
	}
}
