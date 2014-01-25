<?php

class Classifieds_Classifieds_Hook extends Hook
{
	public function usersProfileViewCounters($counters, $user)
	{
		if ( users_helper::isLoggedin() && $user['user_id'] == session::item('user_id') )
		{
			$counters['total_classifieds'] = session::item('total_classifieds');

			return $counters;
		}

		$columns = array(
			'`a`.`user_id`='.$user['user_id'],
			'`a`.`post_date`>' . ( date_helper::now() - config::item('ad_expiration', 'classifieds')*60*60*24 ),
		);

		$params = array();

		loader::model('classifieds/classifieds');

		$counters['total_classifieds'] = $this->classifieds_model->countAds($columns, array(), $params);

		return $counters;
	}

	public function usersSettingsPrivacyOptions($settings, $user = array())
	{
		if ( config::item('timeline_active', 'timeline') && isset($settings['privacy_timeline']) )
		{
			$settings['privacy_timeline']['items']['timeline_classified_post'] = __('timeline_classified_post', 'users_privacy');
			$settings['privacy_timeline']['rules']['callback__parse_config_array']['items'][] = 'timeline_classified_post';
			if ( $user && ( !isset($user['config']['timeline_classified_post']) || $user['config']['timeline_classified_post'] ) || !$user && ( session::item('timeline_classified_post', 'config') === false || session::item('timeline_classified_post', 'config') ) )
			{
				$settings['privacy_timeline']['value']['timeline_classified_post'] = 1;
			}
		}

		return $settings;
	}

	public function usersViewActionsAds($user)
	{
		if ( !$user['total_classifieds'] )
		{
			return '';
		}

		echo '<li class="classifieds">'.html_helper::anchor('classifieds/user/' . text_helper::entities($user['username']), __('classifieds', 'system_navigation').' (' . $user['total_classifieds'] . ')').'</li>';
	}

	public function usersProfileViewSidebarAds($user)
	{
		if ( !$user['total_classifieds'] )
		{
			return '';
		}

		loader::helper('classifieds/classifieds');

		echo classifieds_helper::getAds(array('user' => $user, 'limit' => 4, 'select_users' => false, 'template' => 'classifieds/helpers/classifieds_list'));
	}

	public function usersDelete($userID, $user)
	{
		if ( !( $user['total_classifieds'] + $user['total_classifieds_i'] ) )
		{
			return true;
		}

		loader::model('classifieds/classifieds');
		loader::model('classifieds/pictures', array(), 'classifieds_pictures_model');

		$retval = $this->classifieds_model->deleteUser($userID, $user);

		return $retval;
	}
}
