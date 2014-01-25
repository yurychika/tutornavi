<?php

class Users_Friends_Model extends Model
{
	public function addFriend($userID)
	{
		$request = array(
			'user_id' => session::item('user_id'),
			'friend_id' => $userID,
			'post_date' => date_helper::now(),
			'active' => 0,
		);

		// Insert request
		$this->db->insert('users_friends', $request);

		// Update counters
		$this->db->query("UPDATE `:prefix:users` SET `total_friends_i`=`total_friends_i`+1 WHERE `user_id`=? LIMIT 1", array($userID));

		// Action hook
		hook::action('users/friends/request', session::item('user_id'), $userID);

		return true;
	}

	public function confirmRequest($userID)
	{
		// Confirm request
		$retval = $this->db->update('users_friends', array('active' => 1), array('user_id' => $userID, 'friend_id' => session::item('user_id')), 1);

		// Update counters
		$this->db->query("UPDATE `:prefix:users` SET `total_friends`=`total_friends`+1, `total_friends_i`=`total_friends_i`-1 WHERE `user_id`=? LIMIT 1", array(session::item('user_id')));
		$this->db->query("UPDATE `:prefix:users` SET `total_friends`=`total_friends`+1 WHERE `user_id`=? LIMIT 1", array($userID));

		// Clean up counters
		$this->counters_model->deleteCounters('user', array(session::item('user_id'), $userID));

		// Action hook
		hook::action('users/friends/confirm', session::item('user_id'), $userID);

		return $retval;
	}

	public function getFriend($userID, $active = true)
	{
		if ( ( $user = config::item('u' . $userID, '_users_cache_friends') ) === false )
		{
			$user = $this->db->query("SELECT `user_id`, `friend_id`, `post_date`, `active`
				FROM `:prefix:users_friends`
				WHERE (`user_id`=? AND `friend_id`=? OR `user_id`=? AND `friend_id`=?) LIMIT 1",
				array( session::item('user_id'), $userID, $userID, session::item('user_id') ))->row();

			config::set(array('u' . $userID => $user), '', '_users_cache_friends');
		}

		if ( $active && ( !isset($user['active']) || !$user['active'] ) )
		{
			return array();
		}

		return $user;
	}

	public function getFriends($userID, $active, $order = false, $limit = 15, $params = array())
	{
		// Add condition
		$columns = array();
		if ( $active )
		{
			$columns[] = "(`f`.`user_id`=" . $userID . " OR `f`.`friend_id`=" . $userID . ") AND `f`.`active`=1";
		}
		else
		{
			$columns[] = "`f`.`friend_id`=" . $userID . " AND `f`.`active`=0";
		};

		// Get friends
		$result = $this->fields_model->getRows('user_friend', false,
			false,
			$columns,
			false,
			$order,
			( isset($params['profiles']) && $params['profiles'] ? $limit : false ),
			$params
		);

		$friends = array();

		// Do we have users?
		if ( $result )
		{
			// Loop through users
			foreach ( $result as $user )
			{
				// Parse user fields
				$friends[$user['user_id'] == $userID ? $user['friend_id'] : $user['user_id']] = array(
					'friend_active' => $user['active'],
					'friend_date' => $user['post_date'],
				);
			}

			// Do we need to fetch profiles?
			if ( isset($params['profiles']) && $params['profiles'] )
			{
				// Get users
				$users = $this->users_model->getUsers('in_list', 0, array('`u`.`user_id` IN (' . implode(',', array_keys($friends)) . ')'), array(), false, count($friends));

				// Loop through rows
				foreach ( $users as $user )
				{
					// Does user ID exist?
					if ( isset($friends[$user['user_id']]) )
					{
						// Set user fields
						$friends[$user['user_id']] = array_merge($friends[$user['user_id']], $user);
					}
				}
			}
		}

		return $friends;
	}

	public function deleteFriend($userID, $friendID, $active)
	{
		// Delete request
		$retval = $this->db->query("DELETE FROM `:prefix:users_friends` WHERE `user_id`=? AND `friend_id`=? LIMIT 1", array($userID, $friendID));

		// Update counters
		if ( $active )
		{
			$this->db->query("UPDATE `:prefix:users` SET `total_friends`=`total_friends`-1 WHERE `user_id`=? LIMIT 1", array($userID));
			$this->db->query("UPDATE `:prefix:users` SET `total_friends`=`total_friends`-1 WHERE `user_id`=? LIMIT 1", array($friendID));

			// Action hook
			hook::action('users/friends/delete', $userID, $friendID);
		}
		else
		{
			$this->db->query("UPDATE `:prefix:users` SET `total_friends_i`=`total_friends_i`-1 WHERE `user_id`=? LIMIT 1", array($friendID));

			// Action hook
			hook::action('users/friends/cancel', $userID, $friendID);
		}

		// Clean up counters
		$this->counters_model->deleteCounters('user', array($userID, $friendID));

		return $retval;
	}

	public function deleteUser($userID, $user)
	{
		// Get friends
		$friends = $this->db->query("SELECT * FROM `:prefix:users_friends` WHERE `user_id`=? OR `friend_id`=?", array($userID, $userID))->result();

		foreach ( $friends as $friend )
		{
			if ( $friend['active'] )
			{
				$this->db->query("UPDATE `:prefix:users` SET `total_friends`=`total_friends`-1 WHERE `user_id`=? LIMIT 1", array($friend['user_id'] == $userID ? $friend['friend_id'] : $friend['user_id']));
			}
			elseif ( $friend['user_id'] == $userID )
			{
				$this->db->query("UPDATE `:prefix:users` SET `total_friends_i`=`total_friends_i`-1 WHERE `user_id`=? LIMIT 1", array($friend['friend_id']));
			}
		}

		// Delete friends
		$retval = $this->db->query("DELETE FROM `:prefix:users_friends` WHERE `user_id`=? OR `friend_id`=?", array($userID, $userID));

		// Action hook
		hook::action('users/friends/delete_user', $userID, $user);

		return $retval;
	}
}