<?php

class Timeline_Timeline_Hook extends Hook
{
	public function initialize($config)
	{
		$stream = array();

		loader::model('timeline/timeline');

		$types = $this->timeline_model->getTypes();

		$keywords = array();
		foreach ( $types as $typeID => $type )
		{
			$keywords[$typeID] = $type['type_id'];

			$types[$type['type_id']] = $type['resource_id'];

			unset($types[$typeID]);
		}

		$config['settings']['timeline']['keywords'] = $keywords;
		$config['settings']['timeline']['resources'] = $types;

		return $config;
	}

	public function usersSettingsPrivacyOptions($settings, $user = array())
	{
		if ( config::item('timeline_active', 'timeline') && isset($settings['privacy_timeline']) )
		{
			$settings['privacy_timeline']['items']['timeline_message_post'] = __('timeline_message_post', 'users_privacy');
			$settings['privacy_timeline']['rules']['callback__parse_config_array']['items'][] = 'timeline_message_post';
			if ( $user && ( !isset($user['config']['timeline_message_post']) || $user['config']['timeline_message_post'] ) || !$user && ( session::item('timeline_message_post', 'config') === false || session::item('timeline_message_post', 'config') ) )
			{
				$settings['privacy_timeline']['value']['timeline_message_post'] = 1;
			}

			$items = $this->users_model->getPrivacyOptions($user ? ( isset($user['config']['privacy_profile']) ? $user['config']['privacy_profile'] : 1 ) : session::item('privacy_profile', 'config'), false);
			// Do we have 'registered users' option?
			if ( isset($items[2]) )
			{
				$items[1] = $items[2];
				unset($items[2]);
				ksort($items);
			}

			$settings['privacy_timeline_messages'] = array(
				'name' => __('privacy_timeline_messages', 'users_privacy'),
				'keyword' => 'privacy_timeline_messages',
				'type' => 'select',
				'items' => $items,
				'value' => $user ? ( isset($user['config']['privacy_timeline_messages']) ? $user['config']['privacy_timeline_messages'] : 2 ) : session::item('privacy_timeline_messages', 'config'),
				'rules' => array('intval', 'callback__parse_config_item' => array('privacy_timeline_messages')),
			);
		}

		return $settings;
	}

	public function postMessage($items, $users)
	{
		$stream = array();

		loader::model('timeline/messages', array(), 'timeline_messages_model');

		$params = array(
			'select_users' => false,
		);

		// Get messages
		$columns = array(
			'`m`.`message_id` IN (' . implode(',', array_keys($items)) . ')',
		);

		$messages = codebreeder::instance()->timeline_messages_model->getMessages(0, $columns, false, count($items), $params);

		foreach ( $items as $itemID => $data )
		{
			if ( isset($messages[$itemID]) && isset($users[$messages[$itemID]['user_id']]) && isset($users[$messages[$itemID]['poster_id']]) )
			{
				foreach ( $data as $actionID => $item )
				{
					$stream[$itemID][$actionID]['html'] = view::load(
						'timeline/timeline/message',
						array('user' => $users[$messages[$itemID]['user_id']], 'poster' => $users[$messages[$itemID]['poster_id']], 'message' => $messages[$itemID], 'params' => $item['params']),
						true
					);

					$stream[$itemID][$actionID]['rating']['total_votes'] = $messages[$itemID]['total_votes'];
					$stream[$itemID][$actionID]['rating']['total_score'] = $messages[$itemID]['total_score'];
					$stream[$itemID][$actionID]['rating']['total_rating'] = $messages[$itemID]['total_rating'];
					$stream[$itemID][$actionID]['rating']['total_likes'] = $messages[$itemID]['total_likes'];
					$stream[$itemID][$actionID]['rating']['type'] = config::item('timeline_rating', 'timeline');

					$stream[$itemID][$actionID]['comments']['total_comments'] = $messages[$itemID]['total_comments'];
					$stream[$itemID][$actionID]['comments']['post'] = session::permission('comments_view', 'comments') && session::permission('comments_post', 'comments') ? true : false;
				}
			}
		}

		return $stream;
	}

	public function cronRun()
	{
		if ( config::item('notices_cleanup_delay', 'timeline') )
		{
			loader::model('timeline/notices', array(), 'timeline_notices_model');

			$this->timeline_notices_model->cleanup();
		}

		return true;
	}

	public function messageTimeline($notice)
	{
		$notice['html'] = __('timeline_message_post', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor(session::item('slug'), '\1')));

		return $notice;
	}

	public function likeTimelineMessage($notice)
	{
		$notice['html'] = __('timeline_message_like', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor(session::item('slug'), '\1')));

		return $notice;
	}

	public function voteTimelineMessage($notice)
	{
		$notice['html'] = __('timeline_message_vote', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor(session::item('slug'), '\1')));

		return $notice;
	}

	public function commentTimelineMessage($notice)
	{
		$notice['html'] = __('timeline_message_comment', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor(session::item('slug'), '\1')));

		return $notice;
	}

	public function likeTimeline($notice)
	{
		$notice['html'] = __('timeline_like', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor(session::item('slug'), '\1')));

		return $notice;
	}

	public function voteTimeline($notice)
	{
		$notice['html'] = __('timeline_vote', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor(session::item('slug'), '\1')));

		return $notice;
	}

	public function commentTimeline($notice)
	{
		$notice['html'] = __('timeline_comment', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor(session::item('slug'), '\1')));

		return $notice;
	}
}
