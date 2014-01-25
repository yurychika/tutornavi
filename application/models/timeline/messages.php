<?php

class Timeline_Messages_Model extends Model
{
	public function saveMessage($messageID, $message, $userID = 0)
	{
		// Is this a new message?
		if ( !$messageID )
		{
			// Message data
			$message = array(
				'user_id' => $userID,
				'poster_id' => session::item('user_id'),
				'message' => $message,
				'post_date' => date_helper::now(),
			);

			// Insert message
			$messageID = $this->db->insert('timeline_messages', $message);

			// Save timeline action
			timeline_helper::save('timeline_message_post', $userID, $messageID, 1, 1, false, array(), session::item('user_id'), ( session::item('timeline_message_post', 'config') === false || session::item('timeline_message_post', 'config') ? 1 : 0 ));

			// Action hook
			hook::action('timeline/messages/insert', session::item('user_id'), $userID, $message['message']);

			// Do we have user id?
			if ( $userID && $userID != session::item('user_id') )
			{
				// Save notification
				timeline_helper::notice('timeline_message_post', $userID, session::item('user_id'));
			}
		}
		// This is an existing message
		else
		{
			// Message data
			$message = array(
				'message' => $message,
			);

			$this->db->update('timeline_messages', $message, array('message_id' => $messageID), 1);

			// Action hook
			hook::action('timeline/messages/update', $messageID, $message['message']);
		}

		return $messageID;
	}

	public function deleteMessage($messageID, $message = array(), $action = true)
	{
		// Delete message
		$retval = $this->db->delete('timeline_messages', array('message_id' => $messageID), 1);
		if ( $retval )
		{
			if ( $action )
			{
				// Delete timeline action
				timeline_helper::delete('timeline_message_post', $message['user_id'], $messageID);
			}

			// Action hook
			hook::action('timeline/messages/delete', $messageID);
		}

		return $retval;
	}

	public function deleteUser($userID, $user)
	{
		// Delete messages
		$retval = $this->db->query("DELETE FROM `:prefix:timeline_messages` WHERE `user_id`=? OR `poster_id`=?", array($userID, $userID));

		// Action hook
		hook::action('timeline/messages/delete_user', $userID, $user);

		return $retval;
	}

	public function getMessage($messageID, $params = array())
	{
		$message = $this->fields_model->getRow('timeline_message', $messageID, false, $params);

		// Escape messages
		if ( $message && ( !isset($params['escape']) || $params['escape'] ) )
		{
			$message['message'] = text_helper::entities($message['message']);
		}

		return $message;
	}

	public function countRecentMessages()
	{
		$time = date_helper::now() - session::permission('messages_delay_time', 'timeline') * ( session::permission('messages_delay_type', 'timeline') == 'minutes' ? 60 : 3600 );

		$messages = $this->db->query("SELECT COUNT(*) AS `totalrows`
			FROM `:prefix:timeline_messages`
			WHERE `poster_id`=? AND `post_date`>?",
			array(session::item('user_id'), $time))->row();

		return $messages['totalrows'];
	}

	public function countMessages($columns = array(), $items = array(), $params = array())
	{
		$userID = isset($params['user_id']) ? $params['user_id'] : 0;

		$params['count'] = 1;

		$total = $this->getMessages($userID, $columns, false, 0, $params);

		return $total;
	}

	public function getMessages($userID = 0, $columns, $order = false, $limit = 15, $params = array())
	{
		// Do we have an user ID?
		if ( $userID )
		{
			$columns[] = "`m`.`user_id`=" . $this->db->escape($userID);
		}

		// Do we need to count messages?
		if ( isset($params['count']) && $params['count'] )
		{
			$total = $this->fields_model->countRows('timeline_message', ( !isset($params['select_users']) || $params['select_users'] ? true : false ), $columns, array(), $params);

			return $total;
		}

		// Get messages
		$messages = $this->fields_model->getRows('timeline_message', ( !isset($params['select_users']) || $params['select_users'] ? true : false ), false, $columns, array(), $order, $limit, $params);

		// Escape messages
		if ( !isset($params['escape']) || $params['escape'] )
		{
			foreach ( $messages as $index => $message )
			{
				$messages[$index]['message'] = text_helper::entities($message['message']);
			}
		}

		return $messages;
	}

	public function getReportedActions()
	{
		$actions = array(
			'delete' => __('report_item_delete', 'reports'),
		);

		return $actions;
	}

	public function runReportedAction($messageID, $action)
	{
		$message = $this->getMessage($messageID);

		if ( $message )
		{
			if ( $action == 'delete' )
			{
				$this->deleteMessage($messageID, $message);
			}
		}

		return true;
	}

	public function getReportedURL($messageID)
	{
		$url = 'cp/plugins/timeline/edit/' . $messageID;

		return $url;
	}
}
