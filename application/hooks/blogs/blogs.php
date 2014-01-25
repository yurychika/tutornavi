<?php

class Blogs_Blogs_Hook extends Hook
{
	public function usersProfileViewCounters($counters, $user)
	{
		if ( users_helper::isLoggedin() && $user['user_id'] == session::item('user_id') )
		{
			$counters['total_blogs'] = session::item('total_blogs');

			return $counters;
		}

		$columns = array(
			'`b`.`user_id`='.$user['user_id'],
			'`b`.`active`=1',
		);

		$params = array(
			'privacy' => 1,
		);

		loader::model('blogs/blogs');

		$counters['total_blogs'] = $this->blogs_model->countBlogs($columns, array(), $params);

		return $counters;
	}

	public function usersSettingsPrivacyOptions($settings, $user = array())
	{
		if ( config::item('timeline_active', 'timeline') && isset($settings['privacy_timeline']) )
		{
			$settings['privacy_timeline']['items']['timeline_blog_post'] = __('timeline_blog_post', 'users_privacy');
			$settings['privacy_timeline']['rules']['callback__parse_config_array']['items'][] = 'timeline_blog_post';
			if ( $user && ( !isset($user['config']['timeline_blog_post']) || $user['config']['timeline_blog_post'] ) || !$user && ( session::item('timeline_blog_post', 'config') === false || session::item('timeline_blog_post', 'config') ) )
			{
				$settings['privacy_timeline']['value']['timeline_blog_post'] = 1;
			}
		}

		return $settings;
	}

	public function usersProfileViewSidebarBlogs($user)
	{
		if ( !$user['total_blogs'] )
		{
			return '';
		}

		loader::helper('blogs/blogs');

		echo blogs_helper::getBlogs(array('user' => $user, 'limit' => 4, 'select_users' => false, 'template' => 'blogs/helpers/blogs_list'));
	}

	public function usersDelete($userID, $user)
	{
		if ( !( $user['total_blogs'] + $user['total_blogs_i'] ) )
		{
			return true;
		}

		loader::model('blogs/blogs');

		$retval = $this->blogs_model->deleteUser($userID, $user);

		return $retval;
	}
}
