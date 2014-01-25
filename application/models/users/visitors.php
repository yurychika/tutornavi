<?php

class Users_Visitors_Model extends Model
{
	public function addVisitor($userID)
	{
		$data = array(
			'post_date' => date_helper::now(),
			'new' => 1,
		);

		// Did we already visit this profile?
		$row = $this->db->query("SELECT `post_date`, `new` FROM `:prefix:users_visitors` WHERE `user_id`=? AND `visitor_id`=? LIMIT 1", array($userID, session::item('user_id')))->row();

		if ( $row )
		{
			if ( !$row['new'] )
			{
				// Update counters
				$this->db->query("UPDATE `:prefix:users` SET `total_visitors_new`=`total_visitors_new`+1 WHERE `user_id`=? LIMIT 1", array($userID));
			}

			// Update visitor
			$this->db->update('users_visitors', $data, array('user_id' => $userID, 'visitor_id' => session::item('user_id')), 1);
		}
		else
		{
			$data['user_id'] = $userID;
			$data['visitor_id'] = session::item('user_id');

			// Insert visitor
			$this->db->insert('users_visitors', $data);

			// Update counters
			$this->db->query("UPDATE `:prefix:users` SET `total_visitors`=`total_visitors`+1, `total_visitors_new`=`total_visitors_new`+1 WHERE `user_id`=? LIMIT 1", array($userID));
		}

		// Action hook
		hook::action('users/visitors/add', $userID, session::item('user_id'));

		return true;
	}

	public function getVisitors($userID, $order = false, $limit = 15, $params = array())
	{
		// Add condition
		$columns = array();
		$columns[] = "`v`.`user_id`=" . $userID;

		// Get users
		$result = $this->fields_model->getRows('user_visitor', false,
			false,
			$columns,
			false,
			$order,
			( isset($params['profiles']) && $params['profiles'] ? $limit : false ),
			$params
		);

		$visitors = array();

		// Do we have users?
		if ( $result )
		{
			// Loop through users
			foreach ( $result as $user )
			{
				// Parse user fields
				$visitors[$user['visitor_id']] = array(
					'visitor_id' => $user['visitor_id'],
					'visitor_date' => $user['post_date'],
					'visitor_new' => $user['new'],
				);
			}

			// Do we need to fetch profiles?
			if ( isset($params['profiles']) && $params['profiles'] )
			{
				// Get users
				$users = $this->users_model->getUsers('in_list', 0, array('`u`.`user_id` IN (' . implode(',', array_keys($visitors)) . ')'), array(), false, count($visitors));

				// Loop through rows
				foreach ( $users as $user )
				{
					// Does user ID exist?
					if ( isset($visitors[$user['user_id']]) )
					{
						// Set user fields
						$visitors[$user['user_id']] = array_merge($visitors[$user['user_id']], $user);
					}
				}
			}
		}

		return $visitors;
	}

	public function resetCounter()
	{
		// Update counters
		$this->db->query("UPDATE `:prefix:users_visitors` SET `new`=0 WHERE `user_id`=? AND `new`=1 LIMIT ?", array(session::item('user_id'), session::item('total_visitors_new')));
		$this->db->query("UPDATE `:prefix:users` SET `total_visitors_new`=0 WHERE `user_id`=? LIMIT 1", array(session::item('user_id')));
	}

	public function deleteUser($userID, $user)
	{
		// Get visitors
		$visitors = $this->db->query("SELECT * FROM `:prefix:users_visitors` WHERE `visitor_id`=?", array($userID))->result();

		foreach ( $visitors as $visitor )
		{
			$this->db->query("UPDATE `:prefix:users` SET `total_visitors`=`total_visitors`-1 " . ( $visitor['new'] ? ", `total_visitors_new`=`total_visitors_new`-1" : "" ) . " WHERE `user_id`=? LIMIT 1", array($visitor['user_id']));
		}

		// Delete visitors
		$retval = $this->db->query("DELETE FROM `:prefix:users_visitors` WHERE `user_id`=? OR `visitor_id`=?", array($userID, $userID));

		// Action hook
		hook::action('users/visitors/delete_user', $userID, $user);

		return $retval;
	}

	public function cleanup()
	{
		$timestamp = date_helper::now()-60*60*24*config::item('cleanup_delay', 'users');

		// Get old visitors
		$visitors = $this->db->query("SELECT * FROM `:prefix:users_visitors` WHERE `post_date`<?", array($timestamp))->result();

		foreach ( $visitors as $visitor )
		{
			$this->db->query("UPDATE `:prefix:users` SET `total_visitors`=`total_visitors`-1 " . ( $visitor['new'] ? ", `total_visitors_new`=`total_visitors_new`-1" : "" ) . " WHERE `user_id`=? LIMIT 1", array($visitor['user_id']));
		}

		// Delete visitors
		$retval = $this->db->query("DELETE FROM `:prefix:users_visitors` WHERE `post_date`<?", array($timestamp));

		// Action hook
		hook::action('users/visitors/cleanup');

		$this->cron_model->addLog('[Users] Deleted ' . count($visitors) . ' user visitors.');

		return $retval;
	}
}