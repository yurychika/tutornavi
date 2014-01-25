<?php

class Messages_Messages_Hook extends Hook
{
	public function usersSettingsNotificationsOptions($settings, $user = array())
	{
		if ( config::item('messages_active', 'messages') )
		{
			$settings['general']['items']['notify_messages'] = __('notify_messages', 'users_notifications');
			$settings['general']['rules']['callback__parse_config_array']['items'][] = 'notify_messages';
			if ( $user && ( !isset($user['config']['notify_messages']) || $user['config']['notify_messages'] ) || !$user && ( session::item('notify_messages', 'config') === false || session::item('notify_messages', 'config') ) )
			{
				$settings['general']['value']['notify_messages'] = 1;
			}
		}

		return $settings;
	}

	public function usersDelete($userID, $user)
	{
		loader::model('messages/messages');

		$retval = $this->messages_model->deleteUser($userID, $user);

		return $retval;
	}
}
