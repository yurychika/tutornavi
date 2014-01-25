<?php

class Timeline_Timeline_Model extends Model
{
	public function saveAction($actionID, $keyword, $userID, $itemID, $active = 1, $privacy = 1, $params = array(), $attachments = array(), $posterID = 0, $posterPrivacy = 1, $resource = 'user', $customID = 0)
	{
		$resourceID = config::item('resources', 'core', $resource, 'resource_id');
		$typeID = config::item('keywords', 'timeline', $keyword);

		if ( !is_array($attachments) )
		{
			$attachments = $attachments ? array($attachments) : array();
		}

		if ( !$actionID )
		{
			$action = array(
				'user_id' => $userID,
				'poster_id' => $posterID,
				'type_id' => $typeID,
				'resource_id' => $resourceID,
				'custom_id' => $customID,
				'item_id' => $itemID,
				'attachments' => count($attachments),
				'post_date' => date_helper::now(),
			);
		}

		if ( $params !== false )
		{
			$action['params'] = $params ? json_encode($params) : null;
		}
		if ( $active !== false )
		{
			$action['active'] = $active ? 1 : 0;
		}
		if ( $privacy !== false )
		{
			$action['privacy'] = $privacy;
		}
		if ( $posterPrivacy !== false )
		{
			$action['poster_privacy'] = $posterPrivacy;
		}

		if ( !$actionID )
		{
			// Save action
			$actionID = $this->db->insert('timeline_actions', $action);

			// Action hook
			hook::action('timeline/actions/insert', $resourceID, $customID, $typeID, $userID, $itemID, $privacy, $action, $attachments);
		}
		else
		{
			// Update action
			if ( $actionID && is_numeric($actionID) )
			{
				$this->db->query("UPDATE `:prefix:timeline_actions` SET `item_id`=?
						" . ( $active !== false ? ", `active`=" . $action['active'] : "" ) . "
						" . ( $privacy !== false ? ", `privacy`=" . $action['privacy'] : "" ) . "
						" . ( $params !== false ? ", `params`='" . $action['params'] . "'" : "" ) . "
						" . ( $attachments ? ", `attachments`=`attachments`+" . count($attachments) : "" ) . "
					WHERE `action_id`=? LIMIT 1",
					array($itemID, $actionID));
			}
			else
			{
				$this->db->query("UPDATE `:prefix:timeline_actions` SET `item_id`=?
						" . ( $active !== false ? ", `active`=" . $action['active'] : "" ) . "
						" . ( $privacy !== false ? ", `privacy`=" . $action['privacy'] : "" ) . "
						" . ( $params !== false ? ", `params`='" . $action['params'] . "'" : "" ) . "
						" . ( $attachments ? ", `attachments`=`attachments`+" . count($attachments) : "" ) . "
					WHERE `resource_id`=? AND `custom_id`=? AND `type_id`=? AND `user_id`=? AND `item_id`=? ORDER BY `post_date` DESC LIMIT 1",
					array($itemID, $resourceID, $customID, $typeID, $userID, $itemID));
			}

			// Action hook
			hook::action('timeline/actions/update', $resourceID, $customID, $typeID, $userID, $itemID, $privacy, $action, $attachments);
		}

		if ( $actionID && is_numeric($actionID) && $attachments )
		{
			foreach ( $attachments as $attachment )
			{
				// Save attachment
				$attachmentID = $this->db->insert('timeline_attachments', array('action_id' => $actionID, 'file_id' => $attachment));
			}
		}

		return $actionID;
	}

	public function deleteAction($actionID, $keyword = '', $userID = 0, $itemID = 0, $resource = 'user', $customID = 0)
	{
		$actions = array(
			'ids' => array(),
			'attachments' => 0,
			'total_likes' => 0,
			'total_votes' => 0,
			'total_comments' => 0,
		);

		if ( $actionID )
		{
			$result = $this->db->query("SELECT `action_id`, `type_id`, `item_id`, `attachments`, `total_likes`, `total_votes`, `total_comments` FROM `:prefix:timeline_actions` WHERE `action_id`=? LIMIT 1", array($actionID))->result();
		}
		else
		{
			$resourceID = config::item('resources', 'core', $resource, 'resource_id');
			$typeID = config::item('keywords', 'timeline', $keyword);

			$result = $this->db->query("SELECT `action_id`, `type_id`, `item_id`, `attachments`, `total_likes`, `total_votes`, `total_comments`
				FROM `:prefix:timeline_actions`
				WHERE `resource_id`=? AND `custom_id`=? AND `type_id`=? AND `user_id`=? AND `item_id`=?", array($resourceID, $customID, $typeID, $userID, $itemID))->result();
		}

		foreach ( $result as $row )
		{
			$actions['ids'][] = $row['action_id'];
			$actions['attachments'] += $row['attachments'];
			$actions['total_likes'] += $row['total_likes'];
			$actions['total_votes'] += $row['total_votes'];
			$actions['total_comments'] += $row['total_comments'];

			if ( $row['type_id'] == config::item('keywords', 'timeline', 'timeline_message_post') )
			{
				// Load messages model
				loader::model('timeline/messages', array(), 'timeline_messages_model');

				$this->timeline_messages_model->deleteMessage($row['item_id'], array(), false);
			}
		}

		if ( $actions['ids'] )
		{
			// Delete comments
			if ( $actions['total_comments'] )
			{
				loader::model('comments/comments');
				$this->comments_model->deleteComments('timeline', $actions['ids'], $actions['total_comments']);
			}

			// Delete likes
			if ( $actions['total_likes'] )
			{
				loader::model('comments/likes');
				$this->likes_model->deleteLikes('timeline', $actions['ids'], $actions['total_likes']);
			}

			// Delete votes
			if ( $actions['total_votes'] )
			{
				loader::model('comments/votes');
				$this->votes_model->deleteVotes('timeline', $actions['ids'], $actions['total_votes']);
			}

			// Delete attachments
			if ( $actions['attachments'] )
			{
				$this->db->query("DELETE FROM `:prefix:timeline_attachments` WHERE `action_id` IN (" . implode(",", $actions['ids']) . ") LIMIT ?", array($actions['attachments']));
			}

			// Delete actions
			$retval = $this->db->delete('timeline_actions', array('action_id' => $actions['ids']), count($actions['ids']));
		}

		// Action hook
		hook::action('timeline/actions/delete', $actions['ids']);

		return true;
	}

	public function deleteUser($userID, $user)
	{
		$actions = array(
			'ids' => array(),
			'attachments' => 0,
		);

		$result = $this->db->query("SELECT `action_id`, `attachments` FROM `:prefix:timeline_actions` WHERE `user_id`=? OR `poster_id`=?", array($userID, $userID))->result();

		foreach ( $result as $row )
		{
			$actions['ids'][] = $row['action_id'];
			$actions['attachments'] += $row['attachments'];
		}

		if ( $actions['ids'] )
		{
			// Delete attachments
			if ( $actions['attachments'] )
			{
				$this->db->query("DELETE FROM `:prefix:timeline_attachments` WHERE `action_id` IN (" . implode(",", $actions['ids']) . ") LIMIT ?", array($actions['attachments']));
			}

			// Delete actions
			$this->db->query("DELETE FROM `:prefix:timeline_actions` WHERE `user_id`=? OR `poster_id`=?", array($userID, $userID));
		}

		// Load messages model
		loader::model('timeline/messages', array(), 'timeline_messages_model');
		$this->timeline_messages_model->deleteUser($userID, $user);

		// Load notices model
		loader::model('timeline/notices', array(), 'timeline_notices_model');
		$this->timeline_notices_model->deleteUser($userID, $user);

		// Action hook
		hook::action('timeline/actions/delete_user', $userID);

		return true;
	}

	public function getAction($actionID, $keyword = '', $userID = 0, $itemID = 0, $timeframe = 12, $resource = 'user', $customID = 0)
	{
		if ( $actionID )
		{
			$action = $this->db->query("SELECT * FROM `:prefix:timeline_actions` WHERE `action_id`=? LIMIT 1", array($actionID))->row();
		}
		else
		{
			$resourceID = config::item('resources', 'core', $resource, 'resource_id');
			$typeID = config::item('keywords', 'timeline', $keyword);

			$timeframe = $timeframe * 60*60;

			$action = $this->db->query("SELECT * FROM `:prefix:timeline_actions`
				WHERE `resource_id`=? AND `custom_id`=? AND `type_id`=? AND `user_id`=? AND `item_id`=? " . ( $timeframe ? " AND `post_date`>? ORDER BY `post_date` DESC" : "" ) . " LIMIT 1",
				array($resourceID, $customID, $typeID, $userID, $itemID, ( date_helper::now() - $timeframe )))->row();
		}

		if ( $action && ( !$action['params'] || !( $action['params'] = @json_decode($action['params'], true) ) ) )
		{
			$action['params'] = array();
		}

		return $action;
	}

	public function isRecentAction($keyword, $userID, $itemID = 0, $timeframe = 1, $resource = 'user', $customID = 0)
	{
		$timeframe = $timeframe * 60*60;

		$action = $this->db->query("SELECT COUNT(*) as `totalrows` FROM `:prefix:timeline_actions`
			WHERE `resource_id`=? AND `custom_id`=? AND `type_id`=? AND `user_id`=? AND `item_id`=? AND `post_date`>?",
			array($resourceID, $customID, config::item('keywords', 'timeline', $keyword), $userID, $itemID, ( date_helper::now() - $timeframe )))->row();

		return $action['totalrows'] ? true : false;
	}

	public function getActions($userID, $privacy = true, $lastID = 0, $limit = 15, $params = array())
	{
		$select = $tables = $columns = array();

		// Set resource ID?
		$columns[] = '`a`.`resource_id`=' . ( isset($params['resource_id']) ? $params['resource_id'] : 1 );

		// Set custom ID?
		$columns[] = '`a`.`custom_id`=' . ( isset($params['custom_id']) ? $params['custom_id'] : 0 );

		// Set last action ID
		if ( $lastID )
		{
			$columns[] = '`a`.`action_id`<' . $lastID;
		}

		// Do we have user ID?
		if ( $userID )
		{
			$columns[] = "(`a`.`user_id`=" . $userID . " OR `a`.`poster_id`=" . $userID . ")";
		}

		// Active status
		if ( users_helper::isLoggedin() )
		{
			if ( !$userID )
			{
				$columns[] = "(`a`.`active`=1 OR `a`.`user_id`=" . session::item('user_id') . ")";
			}
			elseif ( $userID != session::item('user_id') )
			{
				$columns[] = "`a`.`active`=1";
			}
		}
		else
		{
			$columns[] = "`a`.`active`=1";
		}

		// Do we need to validate privacy settings?
		if ( $userID && $privacy )
		{
			// Is user logged in?
			if ( users_helper::isLoggedin() )
			{
				$friends = $this->users_friends_model->getFriend($userID);
				// Is this our timeline?
				if ( $userID != session::item('user_id') )
				{
					$columns[] = "`a`.`privacy` BETWEEN 1 AND " . ( $friends ? '3' : '2' );
				}
			}
			else
			{
				$columns[] = '`a`.`privacy`=1';
			}
			$columns[] = "(`a`.`poster_id`!=" . $userID . " OR `a`.`poster_id`=" . $userID . " AND `a`.`poster_privacy`>0)";
		}
		elseif ( !$userID && $privacy )
		{
			// Is user logged in?
			if ( users_helper::isLoggedin() )
			{
				$select[] = "`f`.`post_date` AS `friends`";
				$tables[] = "LEFT JOIN `:prefix:users_friends` AS `f` ON (`a`.`user_id`=`f`.`user_id` AND `f`.`friend_id`=" . session::item('user_id') . " OR `a`.`user_id`=`f`.`friend_id` AND `f`.`user_id`=" . session::item('user_id') . ") " . ( $privacy == 2 ? "AND `f`.`active`=1" : "");
				$columns[] = "(`a`.`privacy`<=2 OR `a`.`privacy`=3 AND (`a`.`user_id`=" . session::item('user_id') . " OR `f`.`active`=1) OR `a`.`privacy`=9 AND `a`.`user_id`=" . session::item('user_id') . ")";
				if ( $privacy == 2 )
				{
					$columns[] = "(`f`.`user_id` IS NOT NULL OR `a`.`user_id`=" . session::item('user_id') . ")";
				}
			}
			else
			{
				$columns[] = '`a`.`privacy`=1';
			}
			$columns[] = '`a`.`poster_privacy`>0';
		}

		$attachments = 0;
		$actions = $stream = $users = array();

		// Get actions
		$result = $this->db->query("SELECT `a`.* " . ( $select ? ", " . implode(", ", $select) : '' ) . " FROM `:prefix:timeline_actions` AS `a` " . implode(" ", $tables) . " WHERE " . implode(" AND ", $columns) . " ORDER BY `a`.`post_date` DESC LIMIT $limit")->result();

		if ( !$result )
		{
			return array();
		}

		foreach ( $result as $action )
		{
			$users[$action['user_id']] = $action['user_id'];
			if ( $action['poster_id'] )
			{
				$users[$action['poster_id']] = $action['poster_id'];
			}

			if ( !$action['params'] || !( $action['params'] = @json_decode($action['params'], true) ) )
			{
				$action['params'] = array();
			}

			$actions[$action['action_id']] = $action;

			$stream[$action['type_id']][$action['item_id']][$action['action_id']] = array(
				'user_id' => $action['user_id'],
				'poster_id' => $action['poster_id'],
				'attachments' => array(),
				'params' => $action['params'],
			);

			$attachments = $attachments + $action['attachments'];
		}

		// Get attachments
		if ( $attachments )
		{
			$result = $this->db->query("SELECT `action_id`, `attachment_id`, `file_id` FROM `:prefix:timeline_attachments` WHERE `action_id` IN (" . implode(',', array_keys($actions)) . ") LIMIT ?", array($attachments))->result();

			foreach ( $result as $attachment )
			{
				$stream[$actions[$attachment['action_id']]['type_id']][$actions[$attachment['action_id']]['item_id']][$attachment['action_id']]['attachments'][$attachment['file_id']] = $attachment['file_id'];
			}
		}

		// Get users
		$columns = array(
			'`u`.`user_id` IN (' . implode(',', array_keys($users)) . ')',
			'`u`.`verified`=1',
			'`u`.`active`=1',
			'`u`.`group_id` IN (' . implode(',', session::permission('users_groups_browse', 'users')) . ')',
			'`u`.`type_id` IN (' . implode(',', session::permission('users_types_browse', 'users')) . ')',
		);
		$users = $this->users_model->getUsers('in_list', 0, $columns, array(), false, count($users));

		// Update friends status
		foreach ( $actions as $action )
		{
			if ( isset($users[$action['user_id']]) && $users[$action['user_id']] )
			{
				$users[$action['user_id']]['friends'] = isset($friends) ? $friends : ( isset($action['friends']) ? $action['friends'] : false );
			}
		}

		// Create stream
		foreach ( $stream as $typeID => $items )
		{
			$keyword = array_search($typeID, config::item('keywords', 'timeline'));
			$plugin = config::item('resources', 'core', config::item('resources', 'core', config::item('resources', 'timeline', $typeID)), 'plugin');

			$stream[$typeID] = hook::filter('timeline/' . $keyword, $items, $users);
		}

		// Combine actions
		foreach ( $actions as $actionID => $action )
		{
			if ( isset($stream[$action['type_id']][$action['item_id']][$actionID]) )
			{
				$action['relative_resource'] = config::item('resources', 'core', config::item('resources', 'timeline', $action['type_id']));
				$action['relative_resource_id'] = config::item('resources', 'core', $action['relative_resource'], 'resource_id');

				$action['rating'] = isset($stream[$action['type_id']][$action['item_id']][$actionID]['rating']) ? $stream[$action['type_id']][$action['item_id']][$actionID]['rating'] : false;
				$action['comments'] = isset($stream[$action['type_id']][$action['item_id']][$actionID]['comments']) ? $stream[$action['type_id']][$action['item_id']][$actionID]['comments'] : false;

				$action['html'] = $stream[$action['type_id']][$action['item_id']][$actionID]['html'];
				$action['user'] = isset($stream[$action['type_id']][$action['item_id']][$actionID]['user']) ? $stream[$action['type_id']][$action['item_id']][$actionID]['user'] : $users[$action['user_id']];
				if ( $action['poster_id'] )
				{
					$action['poster'] = isset($stream[$action['type_id']][$action['item_id']][$actionID]['poster']) ? $stream[$action['type_id']][$action['item_id']][$actionID]['poster'] : $users[$action['poster_id']];
				}
				else
				{
					$action['poster'] = array();
				}

				$actions[$actionID] = $action;
			}
			else
			{
				unset($actions[$actionID]);
			}
		}

		return $actions;
	}

	public function getTypes()
	{
		$types = array();
		$result = $this->db->query("SELECT * FROM `:prefix:timeline_types`")->result();
		foreach ( $result as $row )
		{
			$types[$row['keyword']] = $row;
		}

		return $types;
	}
}
