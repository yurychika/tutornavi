<?php

class Pictures_Pictures_Hook extends Hook
{
	public function usersProfileViewCounters($counters, $user)
	{
		if ( users_helper::isLoggedin() && $user['user_id'] == session::item('user_id') )
		{
			$counters['total_albums'] = session::item('total_albums');

			return $counters;
		}

		$columns = array(
			'`a`.`user_id`='.$user['user_id'],
		);

		$params = array(
			'privacy' => 1,
		);

		loader::model('pictures/albums', array(), 'pictures_albums_model');

		$counters['total_albums'] = $this->pictures_albums_model->countAlbums($columns, array(), $params);

		return $counters;
	}

	public function usersViewActionsAlbums($user)
	{
		if ( !$user['total_albums'] )
		{
			return '';
		}

		echo '<li class="pictures-albums">'.html_helper::anchor('pictures/user/' . text_helper::entities($user['username']), __('pictures_albums', 'system_navigation').' (' . $user['total_albums'] . ')').'</li>';
	}

	public function usersProfileViewSidebarAlbums($user)
	{
		if ( !$user['total_albums'] )
		{
			return '';
		}

		loader::helper('pictures/pictures');

		echo pictures_helper::getAlbums(array('user' => $user, 'limit' => 4, 'select_users' => false));
	}

	public function usersSettingsPrivacyOptions($settings, $user = array())
	{
		if ( config::item('timeline_active', 'timeline') && isset($settings['privacy_timeline']) )
		{
			$settings['privacy_timeline']['items']['timeline_picture_post'] = __('timeline_picture_post', 'users_privacy');
			$settings['privacy_timeline']['rules']['callback__parse_config_array']['items'][] = 'timeline_picture_post';
			if ( $user && ( !isset($user['config']['timeline_picture_post']) || $user['config']['timeline_picture_post'] ) || !$user && ( session::item('timeline_picture_post', 'config') === false || session::item('timeline_picture_post', 'config') ) )
			{
				$settings['privacy_timeline']['value']['timeline_picture_post'] = 1;
			}
		}

		return $settings;
	}

	public function usersDelete($userID, $user)
	{
		if ( !( $user['total_pictures'] + $user['total_pictures_i'] ) )
		{
			return true;
		}

		loader::model('pictures/albums', array(), 'pictures_albums_model');

		$retval = $this->pictures_albums_model->deleteUser($userID, $user);

		return $retval;
	}
}
