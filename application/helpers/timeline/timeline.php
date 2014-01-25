<?php defined('SYSPATH') || die('No direct script access allowed.');

class Timeline_Helper
{
	static public function save($keyword, $userID, $itemID = 0, $active = 1, $privacy = 1, $params = false, $attachments = array(), $posterID = 0, $posterPrivacy = 1, $resource = 'user', $customID = 0)
	{
		if ( !config::item('plugins', 'core', 'timeline') || !config::item('timeline_active', 'timeline') )
		{
			return false;
		}

		loader::model('timeline/timeline');

		$retval = codebreeder::instance()->timeline_model->saveAction(0, $keyword, $userID, $itemID, $active, $privacy, $params, $attachments, $posterID, $posterPrivacy, $resource, $customID);

		return $retval;
	}

	static public function update($actionID, $keyword, $userID, $itemID, $active = false, $privacy = false, $params = false, $attachments = array(), $posterID = 0, $posterPrivacy = false, $resource = 'user', $customID = 0)
	{
		if ( !config::item('plugins', 'core', 'timeline') || !config::item('timeline_active', 'timeline') )
		{
			return false;
		}

		loader::model('timeline/timeline');

		$retval = codebreeder::instance()->timeline_model->saveAction($actionID, $keyword, $userID, $itemID, $active, $privacy, $params, $attachments, $posterID, $posterPrivacy, $resource, $customID);

		return $retval;
	}

	static public function get($keyword, $userID, $itemID = 0, $timeframe = 12)
	{
		if ( !config::item('plugins', 'core', 'timeline') || !config::item('timeline_active', 'timeline') )
		{
			return false;
		}

		loader::model('timeline/timeline');

		$action = codebreeder::instance()->timeline_model->getAction(0, $keyword, $userID, $itemID, $timeframe);

		return $action;
	}

	static public function delete($keyword, $userID, $itemID = 0, $resource = 'user', $customID = 0)
	{
		if ( !config::item('plugins', 'core', 'timeline') || !config::item('timeline_active', 'timeline') )
		{
			return false;
		}

		loader::model('timeline/timeline');

		$retval = codebreeder::instance()->timeline_model->deleteAction(0, $keyword, $userID, $itemID, $resource, $customID);

		return $retval;
	}

	static public function notice($keyword, $userID, $posterID, $itemID = 0, $childID = 0)
	{
		loader::model('timeline/notices', array(), 'timeline_notices_model');

		$retval = codebreeder::instance()->timeline_notices_model->saveNotice($keyword, $userID, $posterID, $itemID, $childID);

		return $retval;
	}

	static public function unnotice($keyword, $userID, $posterID, $itemID = 0, $childID = 0)
	{
		if ( !config::item('plugins', 'core', 'timeline') || !config::item('timeline_active', 'timeline') )
		{
			return false;
		}

		loader::model('timeline/notices', array(), 'timeline_notices_model');

		$retval = codebreeder::instance()->timeline_notices_model->deleteNotice($keyword, $userID, $posterID, $itemID, $childID);

		return $retval;
	}

	static public function getTimeline($user = array(), $privacy = 2, $template = 'timeline/helpers/timeline')
	{
		loader::model('timeline/timeline');

		// Get actions
		$actions = codebreeder::instance()->timeline_model->getActions($user ? $user['user_id'] : 0, true, 0, config::item('actions_per_page', 'timeline'));

		$ratings = array();
		// Do we have actions and are we logged in?
		if ( $actions && users_helper::isLoggedin() )
		{
			foreach ( $actions as $action )
			{
				if ( $action['rating'] )
				{
					$ratings[$action['relative_resource']][] = $action['item_id'];
				}
				else
				{
					$ratings['timeline'][] = $action['action_id'];
				}
			}

			// Load votes and like models
			loader::model('comments/votes');
			loader::model('comments/likes');

			// Get likes and votes
			$likes = codebreeder::instance()->likes_model->getMultiLikes($ratings);
			$votes = codebreeder::instance()->votes_model->getMultiVotes($ratings);

			$ratings = $likes + $votes;
		}

		// Can we post messages?
		$post = session::permission('messages_post', 'timeline') && codebreeder::instance()->users_model->getPrivacyAccess($user['user_id'], $privacy, false) ? true : false;

		view::assign(array('actions' => $actions, 'user' => $user, 'post' => $post, 'ratings' => $ratings), '', $template);

		// Update comments pagination
		config::set('comments_per_page', config::item('comments_per_page', 'timeline'), 'comments');

		return view::load($template, array(), 1);
	}
}