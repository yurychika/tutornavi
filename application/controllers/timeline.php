<?php

class Timeline_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( !config::item('timeline_active', 'timeline') )
		{
			error::show404();
		}

		loader::model('timeline/timeline');
	}

	public function index()
	{
		// Post message
		if ( input::post('post') && session::permission('messages_post', 'timeline') )
		{
			$this->_saveMessage((int)input::post('user_id'));
		}
		// Delete message
		elseif ( input::post('delete') && session::permission('actions_delete', 'timeline') )
		{
			$this->_deleteAction((int)input::post('delete'));
		}
		// Are we viewing someone?
		elseif ( uri::segment(3) )
		{
			$this->user();
		}
		else
		{
			$this->browse();
		}
	}

	public function browse($privacy = 1)
	{
		if ( $privacy == 1 && !config::item('timeline_public_feed', 'timeline') )
		{
			if ( users_helper::isLoggedin() && config::item('timeline_user_feed', 'timeline') )
			{
				$privacy = 2;
			}
			else
			{
				error::show404();
			}
		}

		// Get last action ID
		$lastID = (int)input::post_get('last_id', 0);

		// Does user have permission to view this user group/type?
		if ( !session::permission('users_groups_browse', 'users') || !session::permission('users_types_browse', 'users') )
		{
			view::noAccess();
		}

		// Get actions
		$actions = $this->timeline_model->getActions(0, $privacy, $lastID, config::item('actions_per_page', 'timeline'));

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
			$likes = $this->likes_model->getMultiLikes($ratings);
			$votes = $this->votes_model->getMultiVotes($ratings);

			$ratings = $likes + $votes;
		}

		// Can we post messages?
		$post = session::permission('messages_post', 'timeline') ? true : false;

		// Update comments pagination
		config::set('comments_per_page', config::item('comments_per_page', 'timeline'), 'comments');

		// Set meta tags
		$this->metatags_model->set('timeline', 'timeline_index');

		// Set title
		view::setTitle(__($privacy == 2 ? 'my_timeline' : 'timeline_feed', 'system_navigation'), false);

		// Assign actions
		if ( $privacy == 1 && config::item('timeline_user_feed', 'timeline') && users_helper::isLoggedin() )
		{
			view::setAction('timeline/manage', __('timeline_user', 'timeline'), array('class' => 'icon-text icon-timeline-public'));
		}
		elseif ( $privacy == 2 && config::item('timeline_public_feed', 'timeline') )
		{
			view::setAction('timeline', __('timeline_public', 'timeline'), array('class' => 'icon-text icon-timeline-public'));
		}

		// Load view
		if ( input::isAjaxRequest() )
		{
			$output = view::load('timeline/actions', array('actions' => $actions, 'user' => array(), 'post' => $post, 'ratings' => $ratings), true);

			view::ajaxResponse($output);
		}
		else
		{
			view::load('timeline/index', array('actions' => $actions, 'user' => array(), 'post' => $post, 'ratings' => $ratings));
		}
	}

	public function manage()
	{
		if ( !users_helper::isLoggedin() || !config::item('timeline_user_feed', 'timeline') )
		{
			if ( !config::item('timeline_public_feed', 'timeline') )
			{
				error::show404();
			}
			else
			{
				$this->browse(1);
			}
		}
		else
		{
			$this->browse(2);
		}
	}

	public function user()
	{
		// Get user and last action ID
		$slugID = urldecode(utf8::trim(uri::segment(3)));
		$lastID = (int)input::post_get('last_id', 0);

		// Get user
		if ( !( $user = $this->users_model->getUser($slugID) ) || !$user['active'] || !$user['verified'] )
		{
			error::show404();
		}

		// Does user have permission to view this user group/type?
		if ( !in_array($user['group_id'], session::permission('users_groups_browse', 'users')) || !in_array($user['type_id'], session::permission('users_types_browse', 'users')) )
		{
			view::noAccess();
		}
		// Validate profile privacy
		elseif ( !$this->users_model->getPrivacyAccess($user['user_id'], ( isset($user['config']['privacy_profile']) ? $user['config']['privacy_profile'] : 1 )) )
		{
			view::noAccess($user['slug']);
		}

		// Get actions
		$actions = $this->timeline_model->getActions($user['user_id'], 1, $lastID, config::item('actions_per_page', 'timeline'));

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
			$likes = $this->likes_model->getMultiLikes($ratings);
			$votes = $this->votes_model->getMultiVotes($ratings);

			$ratings = $likes + $votes;
		}

		// Can we post messages?
		$post = session::permission('messages_post', 'timeline') && $this->users_model->getPrivacyAccess($user['user_id'], ( isset($user['config']['privacy_timeline_messages']) ? $user['config']['privacy_timeline_messages'] : 1 ), false) ? true : false;

		// Update comments pagination
		config::set('comments_per_page', config::item('comments_per_page', 'timeline'), 'comments');

		// Set meta tags
		$this->metatags_model->set('timeline', 'timeline_user', array('user' => $user));

		// Set title
		view::setTitle(__('timeline_recent', 'system_navigation'), false);

		// Set trail
		view::setTrail($user['slug'], $user['name']);

		// Load view
		if ( input::isAjaxRequest() )
		{
			$output = view::load('timeline/actions', array('actions' => $actions, 'user' => $user, 'post' => $post, 'ratings' => $ratings), true);

			view::ajaxResponse($output);
		}
		else
		{
			view::load('timeline/index', array('actions' => $actions, 'user' => $user, 'post' => $post, 'ratings' => $ratings));
		}
	}

	protected function _saveMessage($userID)
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			return false;
		}

		if ( $userID )
		{
			// Get user
			if ( !( $user = $this->users_model->getUser($userID) ) || !$user['active'] || !$user['verified'] )
			{
				return false;
			}

			// Does user have permission to view this user group/type?
			if ( !in_array($user['group_id'], session::permission('users_groups_browse', 'users')) || !in_array($user['type_id'], session::permission('users_types_browse', 'users')) )
			{
				return false;
			}
			// Validate profile privacy
			elseif ( !$this->users_model->getPrivacyAccess($user['user_id'], ( isset($user['config']['privacy_profile']) ? $user['config']['privacy_profile'] : 1 )) )
			{
				return false;
			}
			// Validate posting privacy
			elseif ( !session::permission('messages_post', 'timeline') || !$this->users_model->getPrivacyAccess($user['user_id'], ( isset($user['config']['privacy_timeline_messages']) ? $user['config']['privacy_timeline_messages'] : 1 ), false) )
			{
				return false;
			}
		}
		else
		{
			$userID = session::item('user_id');
		}

		// Load messages model
		loader::model('timeline/messages', array(), 'timeline_messages_model');

		// Create rules
		$rules = array(
			'message' => array(
				'label' => __('message', 'timeline'),
				'rules' => array('trim', 'required', 'min_length' => config::item('message_min_length', 'timeline'), 'max_length' => config::item('message_max_length', 'timeline'), 'callback__is_messages_delay'),
			),
		);

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			$output = view::load('timeline/post', array('user' => $user), true);
			view::ajaxError($output);
		}

		// Get message
		$message = input::post('message');

		// Save message
		if ( !( $messageID = $this->timeline_messages_model->saveMessage(0, $message, $userID) ) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		$actions = $this->timeline_model->getActions($userID, 0, 0, 1);

		$output = view::load('timeline/actions', array('actions' => $actions), true);

		view::ajaxResponse($output);
	}

	protected function _deleteAction($actionID)
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			return false;
		}

		// Validate message ID
		if ( !$actionID || $actionID <= 0 )
		{
			return false;
		}

		// Get action
		$action = $this->timeline_model->getAction($actionID);

		// Does action exist and valid for deletion?
		if ( !$action || ( $action['user_id'] != session::item('user_id') && $action['poster_id'] != session::item('user_id') ) )
		{
			return false;
		}

		// Delete action
		if ( !$this->timeline_model->deleteAction($actionID) )
		{
			return false;
		}

		view::ajaxResponse('ok');
	}

	public function _is_messages_delay()
	{
		if ( session::permission('messages_delay_limit', 'timeline') )
		{
			$messages = $this->timeline_messages_model->countRecentMessages();

			if ( $messages >= session::permission('messages_delay_limit', 'timeline') )
			{
				validate::setError('_is_messages_delay', __('messages_delay_reached', 'timeline', array(
					'%messages' => session::permission('messages_delay_limit', 'timeline'),
					'%time' => session::permission('messages_delay_time', 'timeline'),
					'%type' => utf8::strtolower(__(( session::permission('messages_delay_type', 'timeline') == 'minutes' ? 'minute' : 'hour' ) . ( session::permission('messages_delay_time', 'timeline') > 1 ? 's' : '' ), 'date'))
					)));
				return false;
			}
		}

		return true;
	}
}
