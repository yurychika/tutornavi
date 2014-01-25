<?php

class Users_Blocked_Model extends Model
{
	public function addUser($userID)
	{
		$data = array(
			'user_id' => session::item('user_id'),
			'blocked_id' => $userID,
			'post_date' => date_helper::now(),
		);

		// Insert blocked user
		$this->db->insert('users_blocked', $data);

		// Update counters
		$this->db->query("UPDATE `:prefix:users` SET `total_blocked`=`total_blocked`+1 WHERE `user_id`=? LIMIT 1", array(session::item('user_id')));

		// Action hook
		hook::action('users/blocked/add', session::item('user_id'), $userID);

		return true;
	}

	public function getUser($userID, $self = false)
	{
		if ( ( $user = config::item('u' . $userID, '_users_cache_blacklist') ) === false )
		{
			$user = $this->db->query("SELECT `user_id`, `blocked_id`, `post_date`
				FROM `:prefix:users_blocked`
				WHERE `user_id`=? AND `blocked_id`=? " . ( !$self ? "OR `user_id`=? AND `blocked_id`=?" : "" ) . " LIMIT 1",
				array(session::item('user_id'), $userID, $userID, session::item('user_id')))->row();

			if ( !$self )
			{
				config::set(array('u' . $userID => $user), '', '_users_cache_blacklist');
			}
		}

		if ( $self && ( !isset($user['user_id']) || $user['blocked_id'] != $userID ) )
		{
			return array();
		}

		return $user;
	}

	public function getUsers($userID, $order = false, $limit = 15, $params = array())
	{
		// Add condition
		$columns = array();
		$columns[] = "`b`.`user_id`=" . $userID;

		// Get users
		$result = $this->fields_model->getRows('user_blocked', false,
			false,
			$columns,
			false,
			$order,
			( isset($params['profiles']) && $params['profiles'] ? $limit : false ),
			$params
		);

		$blocked = array();

		// Do we have users?
		if ( $result )
		{
			// Loop through users
			foreach ( $result as $user )
			{
				// Parse user fields
				$blocked[$user['blocked_id']] = array(
					'blocked_id' => $user['blocked_id'],
					'blocked_date' => $user['post_date'],
				);
			}

			// Do we need to fetch profiles?
			if ( isset($params['profiles']) && $params['profiles'] )
			{
				// Get users
				$users = $this->users_model->getUsers('in_list', 0, array('`u`.`user_id` IN (' . implode(',', array_keys($blocked)) . ')'), array(), false, count($blocked));

				// Loop through rows
				foreach ( $users as $user )
				{
					// Does user ID exist?
					if ( isset($blocked[$user['user_id']]) )
					{
						// Set user fields
						$blocked[$user['user_id']] = array_merge($blocked[$user['user_id']], $user);
					}
				}
			}
		}

		return $blocked;
	}

	public function deleteBlockedUser($userID, $blockedID)
	{
		// Delete blocked user
		$retval = $this->db->query("DELETE FROM `:prefix:users_blocked` WHERE `user_id`=? AND `blocked_id`=? LIMIT 1", array($userID, $blockedID));

		// Update counters
		$this->db->query("UPDATE `:prefix:users` SET `total_blocked`=`total_blocked`-1 WHERE `user_id`=? LIMIT 1", array($userID));

		// Action hook
		hook::action('users/blocked/delete', $userID, $blockedID);

		return $retval;
	}

	public function deleteUser($userID, $user)
	{
		// Get users
		$users = $this->db->query("SELECT * FROM `:prefix:users_blocked` WHERE `blocked_id`=?", array($userID))->result();

		foreach ( $users as $user )
		{
			$this->db->query("UPDATE `:prefix:users` SET `total_blocked`=`total_blocked`-1 WHERE `user_id`=? LIMIT 1", array($user['user_id']));
		}

		// Delete blocked users
		$retval = $this->db->query("DELETE FROM `:prefix:users_blocked` WHERE `user_id`=? OR `blocked_id`=?", array($userID, $userID));

		// Action hook
		hook::action('users/blocked/delete_user', $userID, $user);

		return $retval;
	}
}