<?php

class Messages_Messages_Model extends Model
{
	public function saveConversation($conversationID, $subject, $message, $recipients)
	{
		// Set conversation and message data
		$conversation = array(
			'subject' => $subject,
		);
		$message = array(
			'message' => $message,
		);

		// Is this a new conversation?
		if ( !$conversationID )
		{
			// Conversation data
			$conversation['user_id'] = session::item('user_id');
			$conversation['last_post_date'] = date_helper::now();
			$conversation['total_recipients'] = count($recipients);
			$conversation['total_messages'] = 1;

			// Save conversation
			if ( !( $conversationID = $this->db->insert('messages', $conversation) ) )
			{
				return 0;
			}

			// Message data
			$message['conversation_id'] = $conversationID;
			$message['user_id'] = session::item('user_id');
			$message['post_date'] = date_helper::now();

			// Save message
			$messageID = $this->db->insert('messages_data', $message);

			// Update last message ID
			$this->db->update('messages', array('last_message_id' => $messageID), array('conversation_id' => $conversationID), 1);

			// Save sender
			$sender = array(
				'conversation_id' => $conversationID,
				'recipient_id' => session::item('user_id'),
			);

			$this->db->insert('messages_recipients', $sender);

			// Update user counters
			$this->db->query("UPDATE `:prefix:users` SET `total_conversations`=`total_conversations`+1 WHERE `user_id`=? LIMIT 1", array(session::item('user_id')));

			// Save recipients
			foreach ( $recipients as $recipientID )
			{
				$recipient = array(
					'conversation_id' => $conversationID,
					'recipient_id' => $recipientID,
					'new' => 1,
				);

				$this->db->insert('messages_recipients', $recipient);
			}

			// Update user counters
			$this->db->query("UPDATE `:prefix:users`
				SET `total_conversations`=`total_conversations`+1, `total_conversations_new`=`total_conversations_new`+1
				WHERE `user_id` IN (?) LIMIT ?", array($recipients, count($recipients)));

			// Deduct credits?
			if ( config::item('credits_active', 'billing') && session::permission('messages_credits', 'messages') )
			{
				loader::model('billing/credits');
				$this->credits_model->removeCredits(session::item('user_id'), session::permission('messages_credits', 'messages'));
			}

			// Action hook
			hook::action('messages/conversations/insert', $conversationID, $subject, $message, $recipients);
		}

		return $conversationID;
	}

	public function saveMessage($messageID, $conversationID, $message, $recipients = array())
	{
		// Set message data
		$message = array(
			'conversation_id' => $conversationID,
			'message' => $message,
		);

		// Is this a new message?
		if ( !$messageID )
		{
			// Set message data
			$message['user_id'] = session::item('user_id');
			$message['post_date'] = date_helper::now();

			// Save message
			$messageID = $this->db->insert('messages_data', $message);

			// Update conversation
			$this->db->query("UPDATE `:prefix:messages`
				SET `last_message_id`=?, `last_post_date`=?, `total_messages`=`total_messages`+1
				WHERE `conversation_id`=? LIMIT 1",
				array($messageID, date_helper::now(), $conversationID));

			// Update message status
			$this->db->query("UPDATE `:prefix:messages_recipients`
				SET `new`=1, `deleted`=0
				WHERE `conversation_id`=? AND `recipient_id`!=? LIMIT ?",
				array($conversationID, session::item('user_id'), ( count($recipients) - 1 )));

			// Update counters
			foreach ( $recipients as $recipient )
			{
				// Is this our own user ID?
				if ( $recipient['user_id'] != session::item('user_id') )
				{
					// Is this conversation deleted?
					if ( $recipient['deleted'] )
					{
						$this->db->query("UPDATE `:prefix:users`
							SET `total_conversations`=`total_conversations`+1, `total_conversations_new`=`total_conversations_new`+1
							WHERE `user_id`=? LIMIT 1",
							array($recipient['user_id']));
					}
					// Is this conversation already read?
					elseif ( !$recipient['new'] )
					{
						$this->db->query("UPDATE `:prefix:users`
							SET `total_conversations_new`=`total_conversations_new`+1
							WHERE `user_id`=? LIMIT 1",
							array($recipient['user_id']));
					}
				}
			}

			// Action hook
			hook::action('messages/insert', $messageID, $message);

			// Deduct credits?
			if ( config::item('credits_active', 'billing') && session::permission('messages_credits', 'messages') )
			{
				loader::model('billing/credits');
				$this->credits_model->removeCredits(session::item('user_id'), session::permission('messages_credits', 'messages'));
			}
		}
		else
		{
			// Save message
			$this->db->update('messages_data', $message, array('message_id' => $messageID), 1);

			// Action hook
			hook::action('messages/update', $messageID, $message);
		}

		return $messageID;
	}

	public function markRead($conversationID, $userID)
	{
		// Mark conversation as read
		$retval = $this->db->query("UPDATE `:prefix:messages_recipients` SET `new`=0 WHERE `conversation_id`=? AND `recipient_id`=? LIMIT 1", array($conversationID, $userID));
		if ( $retval )
		{
			// Update user counter
			$this->db->query("UPDATE `:prefix:users` SET `total_conversations_new`=`total_conversations_new`-1 WHERE `user_id`=? LIMIT 1", array($userID));
		}

		return $retval;
	}

	public function verifyRecipients($userIDs)
	{
		$users = $this->db->query("SELECT COUNT(*) AS `totalrows`
			FROM `:prefix:users_friends`
			WHERE `user_id`=? AND `friend_id` IN (?) OR `friend_id`=? AND `user_id` IN (?)
			LIMIT " . count($userIDs), array(session::item('user_id'), $userIDs, session::item('user_id'), $userIDs))->row();

		return $users['totalrows'];
	}

	public function getRecipients($conversationID, $limit)
	{
		$recipients = array();

		// Get recipients
		$result = $this->db->query("SELECT * FROM `:prefix:messages_recipients` WHERE `conversation_id` IN (?) LIMIT ?", array($conversationID, $limit))->result();
		if ( $result )
		{
			// Loop through recipients
			foreach ( $result as $recipient )
			{
				// Set recipient
				$recipients[$recipient['conversation_id']][$recipient['recipient_id']] = array(
					'conversation_id' => $recipient['conversation_id'],
					'user_id' => $recipient['recipient_id'],
					'new' => $recipient['new'],
					'deleted' => $recipient['deleted'],
				);
			}

			// Do we have a single conversation ID?
			if ( is_numeric($conversationID) )
			{
				$recipients = current($recipients);
			}
		}

		return $recipients;
	}

	public function getPeople($conversationID, $limit)
	{
		// Set users var
		$users = array();

		// Get recipients
		$result = $this->db->query("SELECT `recipient_id` FROM `:prefix:messages_recipients` WHERE `conversation_id`=? LIMIT ?", array($conversationID, ( $limit + 1 )))->result();

		// Do we have recipients?
		if ( $result )
		{
			// Loop through recipients
			foreach ( $result as $user )
			{
				// Set recipient
				$users[$user['recipient_id']] = true;
			}

			// Get users
			$users = $this->users_model->getUsers('in_list', 0, array('`u`.`user_id` IN (' . implode(',', array_keys($users)) . ')'), array(), false, count($users));
		}

		return $users;
	}

	public function getConversation($conversationID, $userID, $params = array())
	{
		$params['select_columns'] = "`r`.`new`, `r`.`deleted`";
		$params['join_tables'] = "INNER JOIN `:prefix:messages_recipients` AS `r` ON `c`.`conversation_id`=`r`.`conversation_id`";

		// Get conversation
		$conversation = $this->fields_model->getRow('message_conversation', $conversationID, false, $params);

		$conversation = $this->db->query("SELECT `c`.*, `r`.`new`, `r`.`deleted`
			FROM `:prefix:messages` AS `c` INNER JOIN `:prefix:messages_recipients` AS `r` ON `c`.`conversation_id`=`r`.`conversation_id`
			WHERE `c`.`conversation_id`=? AND `r`.`recipient_id`=? LIMIT 1", array($conversationID, $userID))->row();

		// Do we have a conversation?
		if ( $conversation )
		{
			// Do we need to escape results?
			if ( !isset($params['escape']) || $params['escape'] )
			{
				$conversation['subject'] = text_helper::entities($conversation['subject']);
			}

			// Do we need to fetch messages?
			if ( !isset($params['messages']) || $params['messages'] )
			{
				// Get messages
				$conversation['messages'] = $this->getMessages(array("`conversation_id`=" . $conversationID), '', 0, array('select_users' => false));
			}

			// Do we need to fetch recipients?
			if ( !isset($params['recipients']) || $params['recipients'] )
			{
				// Get recipients
				$conversation['recipients'] = $this->getRecipients($conversationID, ( $conversation['total_recipients'] + 1 ));

				// Get users
				$conversation['users'] = $this->users_model->getUsers('in_list', 0, array('`u`.`user_id` IN (' . implode(',', array_keys($conversation['recipients'])) . ')'), array(), false, ( $conversation['total_recipients'] + 1 ), array('config' => true));
			}
		}

		return $conversation;
	}

	public function getMessage($messageID)
	{
		// Get message
		$message = $this->db->query("SELECT `message_id`, `conversation_id`, `user_id`, `message`, `post_date`
			FROM `:prefix:messages_data`
			WHERE `message_id`=? LIMIT 1",
			array($messageID))->row();

		return $message;
	}

	public function countRecentMessages()
	{
		$time = date_helper::now() - session::permission('messages_delay_time', 'messages') * ( session::permission('messages_delay_type', 'messages') == 'minutes' ? 60 : 3600 );

		$messages = $this->db->query("SELECT COUNT(*) AS `totalrows`
			FROM `:prefix:messages_data`
			WHERE `user_id`=? AND `post_date`>?",
			array(session::item('user_id'), $time))->row();

		return $messages['totalrows'];
	}

	public function countMessages($columns = array(), $items = array(), $params = array())
	{
		$params['count'] = true;

		$total = $this->getMessages($columns, false, 0, $params);

		return $total;
	}

	public function getMessages($columns = array(), $order = false, $limit = 15, $params = array())
	{
		// Do we need to count messages?
		if ( isset($params['count']) && $params['count'] )
		{
			$total = $this->fields_model->countRows('message', ( !isset($params['select_users']) || $params['select_users'] ? true : false ), $columns, array(), $params);

			return $total;
		}

		// Get messages
		$messages = $this->fields_model->getRows('message', ( !isset($params['select_users']) || $params['select_users'] ? true : false ), false, $columns, array(), $order, $limit, $params);

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

	public function countConversations($columns = array(), $params = array())
	{
		$params['count'] = 1;
		$params['join_tables'] = "INNER JOIN `:prefix:messages_recipients` AS `r` ON c.`conversation_id`=r.`conversation_id` INNER JOIN `:prefix:messages_data` AS `m` ON c.`last_message_id`=m.`message_id`";

		$total = $this->getConversations($params);

		return $total;
	}

	public function getConversations($columns = array(), $order = false, $limit = 15, $params = array())
	{
		// Do we need to count conversations?
		if ( isset($params['count']) && $params['count'] )
		{
			$total = $this->fields_model->countRows('message_conversation', false, $columns, array(), $params);

			return $total;

			$row = $this->db->query("SELECT COUNT(c.`conversation_id`) AS `totalrows`
				FROM `:prefix:messages` AS `c` INNER JOIN `:prefix:messages_recipients` AS `r` ON c.`conversation_id`=r.`conversation_id`
					INNER JOIN `:prefix:messages_data` AS `m` ON c.`last_message_id`=m.`message_id`
				" . ( $joinColumns ? " WHERE " . $joinColumns : "" ))->row();

			return $row['totalrows'];
		}

		$params['select_columns'] = "m.`user_id` AS `message_user_id`, m.`message`, m.`post_date` AS `message_post_date`, r.`new`, r.`deleted`";
		$params['join_tables'] = "INNER JOIN `:prefix:messages_recipients` AS `r` ON c.`conversation_id`=r.`conversation_id` INNER JOIN `:prefix:messages_data` AS `m` ON c.`last_message_id`=m.`message_id`";

		// Conversations and recipients vars
		$conversations = $recipientIDs = array();
		$recipients = 0;

		// Get conversations
		$result = $this->fields_model->getRows('message_conversation', false, false, $columns, array(), $order, $limit, $params);

		// Loop through conversations
		foreach ( $result as $conversation )
		{
			// Do we need to escape results?
			if ( !isset($params['escape']) || $params['escape'] )
			{
				$conversation['subject'] = text_helper::entities($conversation['subject']);
				$conversation['message'] = text_helper::entities($conversation['message']);
			}

			// Set conversation
			$conversations['threads'][$conversation['conversation_id']] = $conversation;

			// Count recipients
			$recipients = $recipients + $conversation['total_recipients'] + 1;
		}

		// Do we have conversations?
		if ( $conversations )
		{
			// Get recipients
			$conversations['recipients'] = $this->getRecipients(array_keys($conversations['threads']), $recipients);

			// Get recipient IDs
			foreach ( $conversations['recipients'] as $conversationID => $recipients )
			{
				foreach ( $recipients as $recipientID => $recipient )
				{
					$recipientIDs[$recipientID] = true;
				}

				// Do we have only 1 recipient?
				if ( $conversations['threads'][$conversationID]['total_recipients'] == 1 )
				{
					unset($conversations['recipients'][$conversationID][session::item('user_id')]);
					$conversations['recipients'][$conversationID] = current($conversations['recipients'][$conversationID]);
				}
			}

			// Get users
			$conversations['users'] = $this->users_model->getUsers('in_list', 0, array('`u`.`user_id` IN (' . implode(',', array_keys($recipientIDs)) . ')'), array(), false, count($recipientIDs));
		}

		return $conversations;
	}

	public function deleteMessage($messageID, $conversationID, $message, $conversation)
	{
		// Do we have more than one message in this conversation?
		if ( $conversation['total_messages'] > 1 )
		{
			// Delete message
			$retval = $this->db->delete('messages_data', array('message_id' => $messageID), 1);
			if ( $retval )
			{
				$lastMessageID = $conversation['last_message_id'];
				$lastPostDate = $conversation['last_post_date'];
				// Do we need to update last message ID?
				if ( $conversation['last_message_id'] == $messageID )
				{
					// Get new last message ID
					$lastMessage = $this->db->query("SELECT `message_id`, `post_date` FROM `:prefix:messages_data` WHERE `conversation_id`=? ORDER BY `post_date` DESC LIMIT 1", array($conversationID))->row();
					$lastMessageID = $lastMessage ? $lastMessage['message_id'] : 0;
					$lastPostDate = $lastMessage ? $lastMessage['post_date'] : $lastPostDate;
				}

				// Update message counter
				$this->db->query("UPDATE `:prefix:messages`
					SET `total_messages`=`total_messages`-1, `last_message_id`=?, `last_post_date`=?
					WHERE `conversation_id`=? LIMIT 1",
					array($lastMessageID, $lastPostDate, $conversationID));
			}

			// Action hook
			hook::action('messages/delete', $messageID, $message);
		}
		// Delete conversation
		else
		{
			// Get recipients
			$recipients = $this->db->query("SELECT `conversation_id`, `recipient_id`, `new`, `deleted`
				FROM `:prefix:messages_recipients`
				WHERE `conversation_id`=? LIMIT ?",
				array($conversationID, ($conversation['total_recipients']+1)))->result();

			// Update user counters
			foreach ( $recipients as $recipient )
			{
				// Is this conversation not read yet?
				if ( $recipient['new'] )
				{
					$this->db->query("UPDATE `:prefix:users` SET `total_conversations`=`total_conversations`-1, `total_conversations_new`=`total_conversations_new`-1 WHERE `user_id`=? LIMIT 1", array($recipient['recipient_id']));
				}
				// Is this conversation deleted?
				elseif ( !$recipient['deleted'] )
				{
					$this->db->query("UPDATE `:prefix:users` SET `total_conversations`=`total_conversations`-1 WHERE `user_id`=? LIMIT 1", array($recipient['recipient_id']));
				}
			}

			// Delete conversation
			$this->db->delete('messages', array('conversation_id' => $conversationID), 1);
			$this->db->delete('messages_data', array('conversation_id' => $conversationID), $conversation['total_messages']);
			$this->db->delete('messages_recipients', array('conversation_id' => $conversationID), ($conversation['total_recipients']+1));

			// Action hook
			hook::action('messages/conversations/delete', $conversationID, $conversation);
		}

		return true;
	}

	public function deleteConversation($conversationID, $userID, $conversation)
	{
		// Update user counters
		$this->db->query("UPDATE `:prefix:users`
			SET `total_conversations`=`total_conversations`-1 " . ( $conversation['new'] ? ", `total_conversations_new`=`total_conversations_new`-1" : "" ) . "
			WHERE `user_id`=? LIMIT 1", array($userID));

		// Get other conversation participants
		$conversations = $this->db->query("SELECT COUNT(*) AS `total_rows`
			FROM `:prefix:messages_recipients` AS `r`
			WHERE `r`.`conversation_id`=? AND `r`.`recipient_id`!=? AND `r`.`deleted`=0", array($conversationID, $userID))->row();

		// Do other recipients have this converstaion as 'undeleted'?
		if ( $conversations['total_rows'] )
		{
			// Set conversation status to 'deleted'
			$this->db->query("UPDATE `:prefix:messages_recipients` SET `deleted`=1, `new`=0 WHERE `conversation_id`=? AND `recipient_id`=? LIMIT 1", array($conversationID, $userID));
		}
		// All other recipients have already deleted this converstaion so we can delete it from the database
		else
		{
			// Delete conversation
			$this->db->delete('messages', array('conversation_id' => $conversationID), 1);
			$this->db->delete('messages_data', array('conversation_id' => $conversationID), $conversation['total_messages']);
			$this->db->delete('messages_recipients', array('conversation_id' => $conversationID), ( $conversation['total_recipients'] + 1 ));
		}

		// Action hook
		hook::action('messages/conversations/delete', $conversationID, $conversation);

		return true;
	}

	public function deleteUser($userID, $user, $update = false)
	{
		// Get recipients
		$recipients = $this->db->query("SELECT `m`.`conversation_id`, `r2`.`recipient_id`, `r2`.`new`
			FROM `:prefix:messages` AS `m`, `:prefix:messages_recipients` AS `r1`, `:prefix:messages_recipients` AS `r2`
			WHERE `m`.`total_recipients`=1 AND `m`.`conversation_id`=`r1`.`conversation_id` AND `r1`.`recipient_id`=? AND `m`.`conversation_id`=`r2`.`conversation_id` AND `r2`.`new`=1 AND `r2`.`recipient_id`!=?", array($userID, $userID))->result();

		foreach ( $recipients as $recipient )
		{
			// Update unread conversations count
			$this->db->query("UPDATE `:prefix:users` SET `total_conversations_new`=`total_conversations_new`-1 WHERE `user_id`=? LIMIT 1", array($recipient['recipient_id']));
		}

		$conversationIDs = array();

		// Get conversation IDs
		$conversations = $this->db->query("SELECT `m`.`conversation_id`
			FROM `:prefix:messages` AS `m`, `:prefix:messages_recipients` AS `r`
			WHERE `m`.`total_recipients`=1 AND `m`.`conversation_id`=`r`.`conversation_id` AND `r`.`recipient_id`=?", array($userID))->result();

		foreach ( $conversations as $conversation )
		{
			$conversationIDs[] = $conversation['conversation_id'];
		}

		if ( $conversationIDs )
		{
			// Delete conversations, messages and recipients
			$this->db->query("DELETE FROM `:prefix:messages` WHERE `conversation_id` IN (?)", array($conversationIDs));
			$this->db->query("DELETE FROM `:prefix:messages_data` WHERE `conversation_id` IN (?)", array($conversationIDs));
			$this->db->query("DELETE FROM `:prefix:messages_recipients` WHERE `conversation_id` IN (?)", array($conversationIDs));
		}

		if ( $update )
		{
			// Update user counters
			$this->db->update('users', array('total_conversations' => 0, 'total_conversations_new' => 0), array('user_id' => $userID), 1);
		}

		// Action hook
		hook::action('messages/conversations/delete_user', $userID, $user);

		return true;
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
			$conversation = $this->getConversation($message['conversation_id'], $message['user_id'], array('messages' => false, 'recipients' => false));

			if ( $conversation )
			{
				if ( $action == 'delete' )
				{
					$this->deleteMessage($messageID, $message['conversation_id'], $message, $conversation);
				}
			}
		}

		return true;
	}

	public function getReportedURL($messageID)
	{
		$url = 'cp/plugins/messages/edit/' . $messageID;

		return $url;
	}

	public function updateDbCounters()
	{
		$offset = uri::segment(6, 0);
		$section = uri::segment(7, 'messages');
		$step = 50;
		$next = $offset + $step;

		if ( $section == 'messages' )
		{
			// Count users
			$total = $this->db->query("SELECT COUNT(*) as `total_rows` FROM `:prefix:users`")->row();
			$total = $total['total_rows'];

			// Get users
			$users = $this->db->query("SELECT `user_id` FROM `:prefix:users` ORDER BY `user_id` LIMIT ?, ?", array($offset, $step))->result();

			foreach ( $users as $user )
			{
				// Conversations
				$conversations = array(
					'total_conversations' => 0,
					'total_conversations_new' => 0,
				);

				$items = $this->db->query("SELECT COUNT(*) as `total_rows`, `r`.`new` FROM `:prefix:messages` AS `c`, `:prefix:messages_recipients` AS `r` WHERE `c`.`conversation_id`=`r`.`conversation_id` AND `r`.`deleted`=0 AND `r`.`recipient_id`=? GROUP BY `new`", array($user['user_id']))->result();

				foreach ( $items as $item )
				{
					if ( $item['new'] )
					{
						$conversations['total_conversations'] += $item['total_rows'];
						$conversations['total_conversations_new'] = $item['total_rows'];
					}
					else
					{
						$conversations['total_conversations'] = $item['total_rows'];
					}
				}

				$this->db->update('users', $conversations, array('user_id' => $user['user_id']), 1);
			}

			$result = array(
				'output' => __('progress_status', 'utilities_counters', array('%1' => ( $offset + $step < $total ? ( $offset + $step ) : $total ), '%2' => $total)),
				'redirect' => $next < $total ? $next : '0/conversations',
			);
		}
		else
		{
			// Count conversations
			$total = $this->db->query("SELECT COUNT(*) as `total_rows` FROM `:prefix:messages`")->row();
			$total = $total['total_rows'];

			// Get conversations
			$conversations = $this->db->query("SELECT `conversation_id` FROM `:prefix:messages` ORDER BY `conversation_id` LIMIT ?, ?", array($offset, $step))->result();

			foreach ( $conversations as $conversation )
			{
				// Messages
				$messages = array(
					'total_messages' => 0,
				);

				$items = $this->db->query("SELECT COUNT(*) as `total_rows` FROM `:prefix:messages_data` WHERE `conversation_id`=?", array($conversation['conversation_id']))->result();
				foreach ( $items as $item )
				{
					$messages['total_messages'] = $item['total_rows'];
				}

				$this->db->update('messages', $messages, array('conversation_id' => $conversation['conversation_id']), 1);
			}

			$result = array(
				'output' => __('progress_status', 'utilities_counters', array('%1' => ( $offset + $step < $total ? ( $offset + $step ) : $total ), '%2' => $total)),
				'redirect' => $next < $total ? $next . '/conversations' : '',
			);
		}

		return $result;
	}
}
