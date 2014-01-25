<?php

class Users_Visitors_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		elseif ( !config::item('visitors_active', 'users') )
		{
			error::show404();
		}

		loader::model('users/visitors', array(), 'users_visitors_model');
	}

	public function index()
	{
		$this->manage();
	}

	public function manage()
	{
		// Does user have permission to view visitors?
		if ( !session::permission('users_visitors_browse', 'users') )
		{
			view::noAccess();
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
			'total' => session::permission('users_visitors_limit', 'users') && session::permission('users_visitors_limit', 'users') < $user['total_visitors'] ? session::permission('users_visitors_limit', 'users') : $user['total_visitors'],
			'profiles' => true,
		);

		// Process query string
		$qstring = $this->parseQuerystring($params['total']);

		// Get visitors
		$visitors = array();
		if ( $params['total'] )
		{
			$visitors = $this->users_visitors_model->getVisitors(session::item('user_id'), $qstring['order'], $qstring['limit'], $params);
		}
		else
		{
			view::setInfo(__('no_visitors', 'users_visitors'));
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('users/visitors/manage?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => config::item('visitors_per_page', 'users'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('user' => $user, 'visitors' => $visitors, 'fields' => $fields, 'pagination' => $pagination));

		// Dow we have new visitors?
		if ( session::item('total_visitors_new') )
		{
			// Reset new visitors counter
			$this->users_visitors_model->resetCounter();
		}

		// Set title
		view::setTitle(__('my_visitors', 'system_navigation'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('users/visitors/manage',  __('users_visitors', 'system_navigation'));

		// Load view
		view::load('users/visitors/manage');
	}

	protected function parseQuerystring($max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / config::item('visitors_per_page', 'users')) : 0;

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
		$from = ( $qstring['page'] - 1 ) * config::item('visitors_per_page', 'users');
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . ( !$max || config::item('visitors_per_page', 'users') < $max ? config::item('visitors_per_page', 'users') : $max );

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
