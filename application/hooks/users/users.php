<?php

class Users_Users_Hook extends Hook
{
	public function usersProfileViewSidebarFriends($user)
	{
		if ( !$user['total_friends'] )
		{
			return '';
		}

		echo users_helper::getFriends(array('user' => $user, 'limit' => 6));
	}

	public function usersSettingsPrivacyOptions($settings, $user = array())
	{
		$settings['privacy_profile'] = array(
			'name' => __('privacy_profile', 'users_privacy'),
			'keyword' => 'privacy_profile',
			'type' => 'select',
			'items' => $this->users_model->getPrivacyOptions(),
			'value' => $user ? ( isset($user['config']['privacy_profile']) ? $user['config']['privacy_profile'] : 1 ) : session::item('privacy_profile', 'config'),
			'rules' => array('intval', 'callback__parse_config_item' => array('privacy_profile')),
		);

		if ( config::item('friends_active', 'users') )
		{
			$settings['privacy_friends'] = array(
				'name' => __('privacy_friends', 'users_privacy'),
				'keyword' => 'privacy_friends',
				'type' => 'select',
				'items' => $this->users_model->getPrivacyOptions(),
				'value' => $user ? ( isset($user['config']['privacy_friends']) ? $user['config']['privacy_friends'] : 1 ) : session::item('privacy_friends', 'config'),
				'rules' => array('intval', 'callback__parse_config_item' => array('privacy_friends')),
			);
		}

		if ( config::item('timeline_active', 'timeline') && config::item('privacy_edit', 'timeline') )
		{
			$settings['privacy_timeline'] = array(
				'name' => __('privacy_timeline', 'users_privacy'),
				'keyword' => 'privacy_timeline',
				'type' => 'checkbox',
				'items' => array(),
				'value' => array(),
				'rules' => array('callback__parse_config_array' => array('items' => array())),
			);

			$settings['privacy_timeline']['items']['timeline_user_picture'] = __('timeline_user_picture', 'users_privacy');
			$settings['privacy_timeline']['rules']['callback__parse_config_array']['items'][] = 'timeline_user_picture';
			if ( $user && ( !isset($user['config']['timeline_user_picture']) || $user['config']['timeline_user_picture'] ) || !$user && ( session::item('timeline_user_picture', 'config') === false || session::item('timeline_user_picture', 'config') ) )
			{
				$settings['privacy_timeline']['value']['timeline_user_picture'] = 1;
			}
		}

		return $settings;
	}

	public function usersSettingsNotificationsOptions($settings, $user = array())
	{
		if ( config::item('friends_active', 'users') )
		{
			foreach ( array('notify_friends_request', 'notify_friends_accept') as $keyword )
			{
				$settings['general']['items'][$keyword] = __($keyword, 'users_notifications');
				$settings['general']['rules']['callback__parse_config_array']['items'][] = $keyword;
				if ( $user && ( !isset($user['config'][$keyword]) || $user['config'][$keyword] ) || !$user && ( session::item($keyword, 'config') === false || session::item($keyword, 'config') ) )
				{
					$settings['general']['value'][$keyword] = 1;
				}
			}
		}

		return $settings;
	}

	public function cronRun()
	{
		$this->users_model->cleanup();

		if ( config::item('cleanup_delay', 'users') )
		{
			loader::model('users/visitors', array(), 'users_visitors_model');

			$this->users_visitors_model->cleanup();
		}

		return true;
	}
}
