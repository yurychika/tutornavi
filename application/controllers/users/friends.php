<?php

class Users_Friends_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( !config::item('friends_active', 'users') )
		{
			error::show404();
		}
	}

	public function index()
	{
		if ( uri::segment(4) )
		{
			$this->user();
		}
		else
		{
			$this->manage();
		}
	}

	public function user()
	{
		// Does user have permission to view friends?
		if ( !session::permission('users_friends_browse', 'users') )
		{
			view::noAccess();
		}

		// Get URI vars
		$slugID = urldecode(utf8::trim(uri::segment(4)));

		// Do we have a slug ID?
		if ( $slugID == '' )
		{
			error::show404();
		}

		// Is this our own account?
		if ( strcasecmp($slugID, session::item('slug_id')) == 0 )
		{
			$this->manage();
			return;
		}

		// Get user
		if ( !( $user = $this->users_model->getUser($slugID) ) || !$user['active'] || !$user['verified'] )
		{
			error::show404();
		}

		// Validate profile and friends privacy
		if ( !$this->users_model->getPrivacyAccess($user['user_id'], ( isset($user['config']['privacy_profile']) ? $user['config']['privacy_profile'] : 1 )) || !$this->users_model->getPrivacyAccess($user['user_id'], ( isset($user['config']['privacy_friends']) ? $user['config']['privacy_friends'] : 1 )) )
		{
			view::noAccess($user['slug']);
		}

		// Get fields
		$fields = array();
		foreach ( config::item('usertypes', 'core', 'keywords') as $categoryID => $keyword )
		{
			$fields[$categoryID] = $this->fields_model->getFields('users', $categoryID, 'view', 'in_list');
		}

		// Parameters
		$params = array(
			'total' => $user['total_friends'],
			'profiles' => true,
		);

		// Process query string
		$qstring = $this->parseQuerystring($params['total']);

		// Get friends
		$friends = array();
		if ( $params['total'] )
		{
			$friends = $this->users_friends_model->getFriends($user['user_id'], 1, $qstring['order'], $qstring['limit'], $params);
		}
		else
		{
			view::setInfo(__('no_friends_user', 'users_friends'));
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('users/friends/index/' . $user['slug_id'] . '?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => config::item('friends_per_page', 'users'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('user' => $user, 'friends' => $friends, 'fields' => $fields, 'pagination' => $pagination));

		// Set meta tags
		$this->metatags_model->set('users', 'users_friends', array('user' => $user));

		// Set title
		view::setTitle(__('users_friends', 'system_navigation'), false);

		// Set trail
		view::setTrail($user['slug'], $user['name']);
		view::setTrail('users/friends/index/' . $user['slug_id'], __('users_friends', 'system_navigation'));

		// Assign actions
		//view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#friends-search\').toggle();return false;'));

		// Load view
		view::load('users/friends/user');
	}

	public function manage()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}

		// Assign user from session to variable
		$user = session::section('session');

		// Get fields
		$fields = array();
		foreach ( config::item('usertypes', 'core', 'keywords') as $categoryID => $keyword )
		{
			$fields[$categoryID] = $this->fields_model->getFields('users', $categoryID, 'view', 'in_list');
		}

		// Parameters
		$params = array(
			'total' => $user['total_friends'],
			'profiles' => true,
		);

		// Process query string
		$qstring = $this->parseQuerystring($params['total']);

		// Get friends
		$friends = array();
		if ( $params['total'] )
		{
			$friends = $this->users_friends_model->getFriends(session::item('user_id'), 1, $qstring['order'], $qstring['limit'], $params);
		}
		else
		{
			view::setInfo(__('no_friends_user', 'users_friends'));
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('users/friends/manage?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => config::item('friends_per_page', 'users'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('user' => $user, 'friends' => $friends, 'fields' => $fields, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('my_friends', 'system_navigation'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('users/friends/manage',  __('users_friends', 'system_navigation'));

		// Assign actions
		if ( session::item('total_friends_i') )
		{
			view::setAction('users/friends/requests', __('friends_requests_num' . ( session::item('total_friends_i') == 1 ? '_one' : ''), 'system_info', array('%requests' => session::item('total_friends_i'))), array('class' => 'icon-text icon-users-friends-requests'));
		}

		// Load view
		view::load('users/friends/manage');
	}

	public function requests()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}

		// Assign user from session to variable
		$user = session::section('session');

		// Get fields
		$fields = array();
		foreach ( config::item('usertypes', 'core', 'keywords') as $categoryID => $keyword )
		{
			$fields[$categoryID] = $this->fields_model->getFields('users', $categoryID, 'view', 'in_list');
		}

		// Parameters
		$params = array(
			'total' => $user['total_friends_i'],
			'profiles' => true,
		);

		// Process query string
		$qstring = $this->parseQuerystring($params['total']);

		// Get friends
		$friends = array();
		if ( $params['total'] )
		{
			$friends = $this->users_friends_model->getFriends(session::item('user_id'), 0, $qstring['order'], $qstring['limit'], $params);
		}
		else
		{
			view::setInfo(__('no_friend_requests', 'users_friends'));
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('users/friends/requests?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => config::item('friends_per_page', 'users'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('user' => $user, 'friends' => $friends, 'fields' => $fields, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('friends_requests', 'users_friends'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('users/friends/manage',  __('users_friends', 'system_navigation'));
		// Assign actions
		if ( session::item('total_friends_i') )
		{
			view::setAction('users/friends/requests', __('friends_requests_num' . ( session::item('total_friends_i') == 1 ? '_one' : ''), 'system_info', array('%requests' => session::item('total_friends_i'))), array('class' => 'icon-text icon-users-friends-requests'));
		}
		//view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#friends-search\').toggle();return false;'));

		// Load view
		view::load('users/friends/manage');
	}

	public function add()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}

		// Get URI vars
		$slugID = urldecode(utf8::trim(uri::segment(4)));

		// Do we have a slug ID?
		if ( $slugID == '' )
		{
			error::show404();
		}

		// Get user
		if ( !( $user = $this->users_model->getUser($slugID) ) || !$user['active'] || !$user['verified'] )
		{
			error::show404();
		}
		// Is this a self request
		elseif ( $user['user_id'] == session::item('user_id') )
		{
			router::redirect($user['slug']);
		}

		// Does user have permission to view this user group/type?
		if ( !in_array($user['group_id'], session::permission('users_groups_browse', 'users')) || !in_array($user['type_id'], session::permission('users_types_browse', 'users')) )
		{
			view::noAccess();
		}

		// Did we block this user or did they block us?
		if ( config::item('blacklist_active', 'users') && ( $blocked = $this->users_blocked_model->getUser($user['user_id']) ) )
		{
			if ( $blocked['user_id'] == session::item('user_id') )
			{
				view::setError(__('user_blocked', 'users'));
			}
			else
			{
				view::setError(__('user_blocked_self', 'users'));
			}

			// Load view
			router::redirect($user['slug']);
		}

		// Does friend exist?
		if ( ( $friend = $this->users_friends_model->getFriend($user['user_id'], false) ) )
		{
			// Is request already approved?
			if ( $friend['active'] )
			{
				view::setInfo(__('friend_active', 'users_friends'));
			}
			elseif ( $friend['user_id'] == session::item('user_id') )
			{
				view::setError(__('friend_duplicate', 'users_friends'));
			}
			else
			{
				view::setError(__('friend_duplicate_self', 'users_friends'));
			}

			router::redirect($user['slug']);
		}

		// Add friend request
		$this->users_friends_model->addFriend($user['user_id']);

		// Send friend request email
		if ( !isset($user['config']['notify_friends_request']) || $user['config']['notify_friends_request'] )
		{
			// Create email replacement tags
			$tags = array();
			foreach ( session::section('session') as $key => $value )
			{
				$tags['from.' . $key] = $value;
			}
			$tags = array_merge($tags, $user);
			$tags['friends_link'] = config::siteURL('users/friends/confirm/' . session::item('slug_id'));

			loader::library('email');
			$this->email->sendTemplate('users_friend_request', $user['email'], $tags, $user['language_id']);
		}

		// Success
		view::setInfo(__('friend_requested', 'users_friends'));

		router::redirect($user['slug']);
	}

	public function confirm()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}

		// Get URI vars
		$slugID = urldecode(utf8::trim(uri::segment(4)));

		// Do we have a slug ID?
		if ( $slugID == '' )
		{
			error::show404();
		}

		// Get user
		if ( !( $user = $this->users_model->getUser($slugID) ) || !$user['active'] || !$user['verified'] )
		{
			error::show404();
		}
		// Is this a self request
		elseif ( $user['user_id'] == session::item('user_id') )
		{
			view::setError(__('no_friend_request', 'users_friends'));
			router::redirect($user['slug']);
		}

		// Does friend exist?
		if ( !( $friend = $this->users_friends_model->getFriend($user['user_id'], false) ) || $friend['user_id'] == session::item('user_id') )
		{
			view::setError(__('no_friend_request', 'users_friends'));
			router::redirect($user['slug']);
		}
		// Is request already approved?
		elseif ( $friend['active'] )
		{
			view::setInfo(__('friend_active', 'users_friends'));
			router::redirect($user['slug']);
		}

		// Add friend request
		$this->users_friends_model->confirmRequest($user['user_id']);

		// Create email replacement tags
		$tags = array();
		foreach ( session::section('session') as $key => $value )
		{
			$tags['from.' . $key] = $value;
		}
		$tags = array_merge($tags, $user);

		// Send activation email
		if ( !isset($user['config']['notify_friends_accept']) || $user['config']['notify_friends_accept'] )
		{
			loader::library('email');
			$this->email->sendTemplate('users_friend_confirmed', $user['email'], $tags, $user['language_id']);
		}

		// Success
		view::setInfo(__('friend_confirmed', 'users_friends'));

		router::redirect(input::get('page') ? 'users/friends' . ( session::item('total_friends_i') > 1 ? '/requests' : '' ) : $user['slug']);
	}

	public function delete()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}

		// Get URI vars
		$slugID = urldecode(utf8::trim(uri::segment(4)));

		// Do we have a slug ID?
		if ( $slugID == '' )
		{
			error::show404();
		}

		// Get user
		if ( !( $user = $this->users_model->getUser($slugID) ) || !$user['active'] || !$user['verified'] )
		{
			error::show404();
		}
		// Is this a self request
		elseif ( $user['user_id'] == session::item('user_id') )
		{
			router::redirect($user['slug']);
		}

		// Does friend exist?
		if ( !( $friend = $this->users_friends_model->getFriend($user['user_id'], false) ) )
		{
			view::setError(__('no_friend', 'users_friends'));
			router::redirect($user['slug']);
		}

		// Delete friend
		$this->users_friends_model->deleteFriend($friend['user_id'], $friend['friend_id'], $friend['active']);

		// Success
		view::setInfo(__($friend['active'] ? 'friend_deleted' : 'friend_canceled', 'users_friends'));

		router::redirect(input::get('page') ? 'users/friends/requests' : $user['slug']);
	}

	protected function parseQuerystring($max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / config::item('friends_per_page', 'users')) : 0;

		// Get current page
		$qstring['page'] = (int)input::get('page', 1);
		$qstring['page'] = $qstring['page'] > 0 ? ( !$maxpage || $qstring['page'] <= $maxpage ? $qstring['page'] : $maxpage ) : 1;

		// Get search id
		$qstring['search_id'] = input::get('search_id');

		// Get order field and direction
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('post_date')) ? input::post_get('o') : 'post_date';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'desc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array();

		// Create url string
		$qstring['url'] = ( $qstring['search_id'] ? 'search_id=' . $qstring['search_id'] . '&' : '' ) .
			( $qstring['orderby'] ? 'o=' . $qstring['orderby'] . '&' : '' ) .
			( $qstring['orderby'] && $qstring['orderdir'] ? 'd=' . $qstring['orderdir'] . '&' : '' );

		// Set limit
		$from = ( $qstring['page'] - 1 ) * config::item('friends_per_page', 'users');
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . config::item('friends_per_page', 'users');

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
