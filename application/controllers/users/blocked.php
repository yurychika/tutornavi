<?php

class Users_Blocked_Controller extends Users_Settings_Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( !config::item('blacklist_active', 'users') )
		{
			error::show404();
		}
	}

	public function index()
	{
		$this->manage();
	}

	public function manage()
	{
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
			'total' => $user['total_blocked'],
			'profiles' => true,
		);

		// Process query string
		$qstring = $this->parseQuerystring($params['total']);

		// Get blocked users
		$users = array();
		if ( $params['total'] )
		{
			$users = $this->users_blocked_model->getUsers(session::item('user_id'), $qstring['order'], $qstring['limit'], $params);
		}
		else
		{
			view::setInfo(__('no_blocked_users', 'users_blocked'));
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('users/blocked/manage?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => config::item('blocked_per_page', 'users'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('user' => $user, 'users' => $users, 'fields' => $fields, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('blacklist', 'users'));

		// Load view
		view::load('users/blocked/manage');
	}

	public function add()
	{
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
		if ( $user['user_id'] == session::item('user_id') )
		{
			router::redirect($user['slug']);
		}
		// Does user have permission to view this user group/type?
		elseif ( !in_array($user['group_id'], session::permission('users_groups_browse', 'users')) || !in_array($user['type_id'], session::permission('users_types_browse', 'users')) )
		{
			view::noAccess();
		}

		// Does blocked user exist?
		if ( ( $blocked = $this->users_blocked_model->getUser($user['user_id'], true) ) )
		{
			view::setError(__('user_duplicate', 'users_blocked'));
			router::redirect($user['slug']);
		}

		// Block user
		$this->users_blocked_model->addUser($user['user_id']);

		// Success
		view::setInfo(__('user_blocked', 'users_blocked'));

		//router::redirect($user['slug']);
		router::redirect('users/blocked');
	}

	public function delete()
	{
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

		// Does user exist?
		if ( !( $blocked = $this->users_blocked_model->getUser($user['user_id'], true) ) )
		{
			view::setError(__('no_blocked_user', 'users_blocked'));
			router::redirect('users/blocked');
		}

		// Delete blocked user
		$this->users_blocked_model->deleteBlockedUser(session::item('user_id'), $user['user_id']);

		// Success
		view::setInfo(__('user_unblocked', 'users_blocked'));

		router::redirect(input::get('page') ? 'users/blocked' : $user['slug']);
	}

	protected function parseQuerystring($max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / config::item('blocked_per_page', 'users')) : 0;

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
		$from = ( $qstring['page'] - 1 ) * config::item('blocked_per_page', 'users');
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . config::item('blocked_per_page', 'users');

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
